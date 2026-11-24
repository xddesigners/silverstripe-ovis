<?php

namespace XD\Ovis\Tasks;

use League\Flysystem\FileAttributes;
use League\Flysystem\Filesystem;
use RuntimeException;
use SilverStripe\Assets\File;
use SilverStripe\Assets\FilenameParsing\HashFileIDHelper;
use SilverStripe\Assets\FilenameParsing\NaturalFileIDHelper;
use SilverStripe\Assets\Flysystem\FlysystemAssetStore;
use SilverStripe\Assets\Folder;
use SilverStripe\Assets\Storage\AssetStore;
use SilverStripe\Control\Director;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\PolyExecution\PolyOutput;
use SilverStripe\Subsites\Model\Subsite;
use SilverStripe\Versioned\Versioned;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Throwable;
use XD\Ovis\Models\Presentation;
use XD\Ovis\Models\PresentationMedia;

/**
 * Cleans up what years of imports left behind (the import used to publish every photo on every
 * run and kept the history of deleted photos):
 *
 *  media     photo records whose presentation no longer exists: file, records and history
 *  versions  the version history of every photo (keeps the current draft + live version) and the
 *            history of deleted ovismedia/ folders, deleted in small batches
 *  folders   empty ovismedia/<presentation> folder records
 *  files     files on disk under ovismedia/ (public + protected) that no record uses anymore
 *  optimize  rebuild the shrunk _Versions tables, so the database gives the disk space back
 *
 * Dry run (report only) unless --run. CLI and MySQL / MariaDB only, can take hours on a big site:
 *   vendor/bin/sake tasks:ovis-cleanup
 *   vendor/bin/sake tasks:ovis-cleanup --run
 * See getOptions() for the other options. Safe to stop and run again.
 */
class Cleanup extends BuildTask
{
    protected static string $commandName = 'ovis-cleanup';

    protected string $title = 'Clean up OVIS photos: orphans, version history and stray files';

    protected static string $description = 'Removes orphan photos, their version history and unused files under '
        . 'ovismedia/. Dry run unless --run. Command line only (sake), can take hours.';

    private static bool $can_run_in_browser = false;

    /** Step name => method, in the order they run */
    const STEPS = [
        'media' => 'cleanOrphanMedia',
        'versions' => 'cleanVersions',
        'folders' => 'cleanFolders',
        'files' => 'cleanFiles',
        'optimize' => 'optimizeTables',
    ];

    protected bool $dryRun = true;

    protected int $batchSize = 5000;

    protected int $sleepMs = 100;

    protected int $minAge = 86400;

    protected string $root = PresentationMedia::FOLDER;

    protected PolyOutput $output;

    public function getOptions(): array
    {
        $steps = implode(',', array_keys(self::STEPS));
        return [
            new InputOption('run', null, InputOption::VALUE_NONE, 'Really clean up. Without it nothing is deleted'),
            new InputOption('steps', null, InputOption::VALUE_REQUIRED, 'Run only some steps (always in this order)', $steps),
            new InputOption('batch', null, InputOption::VALUE_REQUIRED, 'Version rows (by ID range) per DELETE', 5000),
            new InputOption('sleep', null, InputOption::VALUE_REQUIRED, 'Pause in ms between batches, keeps a shared database server responsive', 100),
            new InputOption('min-age', null, InputOption::VALUE_REQUIRED, 'Hours: files changed more recently are never deleted (running import)', 24),
            new InputOption('drop-old', null, InputOption::VALUE_NONE, 'Also drop copies of the version tables named *_Versions_old'),
        ];
    }

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $this->output = $output;
        // Dev mode ignores can_run_in_browser
        if (!Director::is_cli()) {
            $this->log('Run this task from the command line: vendor/bin/sake tasks:ovis-cleanup');
            return Command::FAILURE;
        }

        $steps = array_keys(self::STEPS);
        $requested = array_map('trim', explode(',', (string) $input->getOption('steps')));
        if ($unknown = array_diff($requested, $steps)) {
            $this->log('Unknown steps: ' . implode(', ', $unknown) . '. Use: ' . implode(', ', $steps));
            return Command::INVALID;
        }
        $steps = array_values(array_intersect($steps, $requested));
        $this->dryRun = !$input->getOption('run');
        $this->batchSize = max(100, (int) $input->getOption('batch'));
        $this->sleepMs = max(0, (int) $input->getOption('sleep'));
        $this->minAge = (int) round((float) $input->getOption('min-age') * 3600);
        $dropOld = (bool) $input->getOption('drop-old');

        Environment::increaseTimeLimitTo();
        Environment::increaseMemoryLimitTo('1024M');
        if (class_exists(Subsite::class)) {
            Subsite::disable_subsite_filter(true);
        }
        try {
            // MySQL 8 caches table sizes for a day; MariaDB has no such setting
            DB::query('SET SESSION information_schema_stats_expiry = 0');
        } catch (Throwable $e) {
        }

        $this->log(sprintf(
            '%s | steps: %s | batch %d, sleep %d ms, min-age %d h',
            $this->dryRun ? 'DRY RUN, nothing is deleted (add --run to clean up)' : 'RUN',
            implode(', ', $steps),
            $this->batchSize,
            $this->sleepMs,
            $this->minAge / 3600
        ));
        $started = microtime(true);

        Versioned::withVersionedMode(function () use ($steps, $dropOld) {
            Versioned::set_stage(Versioned::DRAFT);
            foreach ($steps as $step) {
                $this->{self::STEPS[$step]}();
            }
            $this->leftoverTables($dropOld);
        });

        $this->log('Finished in ' . $this->duration(microtime(true) - $started));
        return Command::SUCCESS;
    }

    /**
     * Photo records whose presentation is gone (unlinked by the import, or the presentation was
     * deleted). Purged through the module: file + variants, both stages and all history.
     */
    protected function cleanOrphanMedia(): void
    {
        $this->heading('Orphan photos (presentation no longer exists)');
        $schema = DataObject::getSchema();
        $media = $schema->tableName(PresentationMedia::class);
        $presentations = $schema->tableName(Presentation::class);

        if (!(int) DB::query("SELECT COUNT(*) FROM \"$presentations\"")->value()) {
            $this->log('There are no presentations at all (failed import?): not treating every photo as orphan. Skipped.');
            return;
        }

        $ids = DB::query(<<<SQL
            SELECT "m"."ID" FROM "$media" "m"
                LEFT JOIN "$presentations" "p" ON "p"."ID" = "m"."PresentationID" WHERE "p"."ID" IS NULL
            UNION
            SELECT "l"."ID" FROM "{$media}_Live" "l"
                LEFT JOIN "$presentations" "p" ON "p"."ID" = "l"."PresentationID" WHERE "p"."ID" IS NULL
            UNION
            SELECT "l"."ID" FROM "{$media}_Live" "l"
                LEFT JOIN "$media" "m" ON "m"."ID" = "l"."ID" WHERE "m"."ID" IS NULL
            SQL)->column();

        $total = count($ids);
        $this->log("$total orphan photos");
        if ($this->dryRun || !$total) {
            return;
        }

        $purged = $failed = 0;
        $lastReport = microtime(true);
        foreach ($ids as $i => $id) {
            $record = Versioned::get_by_stage(PresentationMedia::class, Versioned::DRAFT)->byID($id)
                ?: Versioned::get_by_stage(PresentationMedia::class, Versioned::LIVE)->byID($id);
            try {
                if (!$record) {
                    throw new RuntimeException('record could not be loaded');
                }
                $record->purgeCompletely();
                $purged++;
            } catch (Throwable $e) {
                $failed++;
                $this->log("  photo $id: {$e->getMessage()}");
            }

            if (($i + 1) % 200 === 0) {
                DataObject::flush_and_destroy_cache();
                gc_collect_cycles();
            }
            if (microtime(true) - $lastReport >= 30) {
                $this->log(sprintf('  %d / %d purged', $i + 1, $total));
                $lastReport = microtime(true);
            }
        }
        $this->log("Purged $purged orphan photos" . ($failed ? ", $failed failed" : ''));
    }

    /**
     * Version history: keep only the current draft + live version of every photo, drop the
     * history of deleted folders under ovismedia/. Deleted by primary-key range in small
     * batches, so no statement holds locks for long and the server stays responsive.
     */
    protected function cleanVersions(): void
    {
        $this->heading('Version history');
        $base = DataObject::getSchema()->baseDataTable(PresentationMedia::class);
        $tables = $this->versionTables();
        $this->reportSizes($tables);

        $mediaClasses = $this->quoteList(ClassInfo::subclassesFor(PresentationMedia::class));
        if ($this->dryRun) {
            $rows = (int) DB::query("SELECT COUNT(*) FROM \"{$base}_Versions\" WHERE \"ClassName\" IN ($mediaClasses)")->value();
            $current = (int) DB::query(<<<SQL
                SELECT COUNT(*) FROM (
                    SELECT "ID", "Version" FROM "$base" WHERE "ClassName" IN ($mediaClasses)
                    UNION
                    SELECT "ID", "Version" FROM "{$base}_Live" WHERE "ClassName" IN ($mediaClasses)
                ) "current"
                SQL)->value();
            $this->log(sprintf(
                '%s photo version rows, %s are current: ~%s rows to delete per table',
                number_format($rows),
                number_format($current),
                number_format(max(0, $rows - $current))
            ));
            return;
        }

        // An unknown root folder must not match "ParentID = 0" (every top level folder)
        $rootID = $this->rootFolderID() ?: -1;
        $folderClass = $this->quote(Folder::class);
        $this->batchDelete("{$base}_Versions", <<<SQL
            DELETE "v" FROM "{$base}_Versions" "v"
            WHERE "v"."ID" BETWEEN %d AND %d
              AND (
                ("v"."ClassName" IN ($mediaClasses)
                  AND NOT EXISTS (SELECT 1 FROM "$base" "d" WHERE "d"."ID" = "v"."RecordID" AND "d"."Version" = "v"."Version")
                  AND NOT EXISTS (SELECT 1 FROM "{$base}_Live" "l" WHERE "l"."ID" = "v"."RecordID" AND "l"."Version" = "v"."Version"))
                OR ("v"."ClassName" = $folderClass AND "v"."ParentID" = $rootID
                  AND NOT EXISTS (SELECT 1 FROM "$base" "d" WHERE "d"."ID" = "v"."RecordID")
                  AND NOT EXISTS (SELECT 1 FROM "{$base}_Live" "l" WHERE "l"."ID" = "v"."RecordID"))
              )
            SQL);

        // Subclass tables (Image, Ovis_PresentationMedia): rows without a base version row
        foreach (array_slice($tables, 1) as $table) {
            $this->batchDelete($table, <<<SQL
                DELETE "v" FROM "$table" "v"
                WHERE "v"."ID" BETWEEN %d AND %d
                  AND NOT EXISTS (
                    SELECT 1 FROM "{$base}_Versions" "f" WHERE "f"."RecordID" = "v"."RecordID" AND "f"."Version" = "v"."Version"
                  )
                SQL);
        }
    }

    /**
     * Folder records under ovismedia/ without any file or folder in them (presentations that
     * are gone). Archived, and their history removed.
     */
    protected function cleanFolders(): void
    {
        $this->heading("Empty folders under {$this->root}/");
        if (!$rootID = $this->rootFolderID()) {
            $this->log("No {$this->root} folder record, nothing to do");
            return;
        }

        $folderClass = $this->quote(Folder::class);
        $empty = <<<SQL
            "f"."ParentID" = $rootID AND "f"."ClassName" = $folderClass
            AND NOT EXISTS (SELECT 1 FROM "File" "c" WHERE "c"."ParentID" = "f"."ID")
            AND NOT EXISTS (SELECT 1 FROM "File_Live" "c" WHERE "c"."ParentID" = "f"."ID")
            SQL;
        $ids = DB::query("SELECT \"f\".\"ID\" FROM \"File\" \"f\" WHERE $empty"
            . " UNION SELECT \"f\".\"ID\" FROM \"File_Live\" \"f\" WHERE $empty")->column();

        $this->log(count($ids) . ' empty folders');
        if ($this->dryRun) {
            return;
        }

        $archived = 0;
        foreach ($ids as $id) {
            // Re-check right before archiving: archiving a folder deletes whatever is in it,
            // and a running import may just have put a photo there
            $stillEmpty = DB::query("SELECT \"f\".\"ID\" FROM \"File\" \"f\" WHERE \"f\".\"ID\" = $id AND $empty"
                . " UNION SELECT \"f\".\"ID\" FROM \"File_Live\" \"f\" WHERE \"f\".\"ID\" = $id AND $empty")->value();
            if (!$stillEmpty) {
                continue;
            }
            $folder = Versioned::get_by_stage(Folder::class, Versioned::DRAFT)->byID($id)
                ?: Versioned::get_by_stage(Folder::class, Versioned::LIVE)->byID($id);
            try {
                $folder?->doArchive();
                DB::query(sprintf('DELETE FROM "File_Versions" WHERE "RecordID" = %d', $id));
                $archived++;
            } catch (Throwable $e) {
                $this->log("  folder $id: {$e->getMessage()}");
            }
        }
        $this->log("Archived $archived empty folders");
    }

    /**
     * Files on disk under ovismedia/ that no record uses: no current file, no kept version.
     * Handles variants (resized copies) and old hashed copies in .protected. Works one
     * presentation folder at a time and removes folders that end up empty.
     */
    protected function cleanFiles(): void
    {
        $this->heading("Stray files under {$this->root}/");
        /** @var FlysystemAssetStore $store */
        $store = Injector::inst()->get(AssetStore::class);
        $keep = $this->keepList();
        $this->log(count($keep['names']) . ' file names in use');
        $cutoff = time() - $this->minAge;

        foreach (['public' => $store->getPublicFilesystem(), 'protected' => $store->getProtectedFilesystem()] as $label => $fs) {
            if (!$fs->has($this->root)) {
                $this->log("$label: no {$this->root}/ folder");
                continue;
            }

            $stats = ['kept' => 0, 'recent' => 0, 'deleted' => 0, 'bytes' => 0, 'dirs' => 0, 'errors' => 0];
            $lastReport = microtime(true);
            foreach ($fs->listContents($this->root)->toArray() as $top) {
                // One presentation folder at a time keeps the memory use bounded
                $items = $top->isDir() ? $fs->listContents($top->path(), true)->toArray() : [$top];
                $survivors = $stats['kept'] + $stats['recent'] + $stats['errors'];
                foreach ($items as $item) {
                    if ($item instanceof FileAttributes) {
                        $this->checkFile($fs, $label, $item, $keep, $cutoff, $stats);
                    }
                }
                if ($top->isDir()) {
                    if (!$this->dryRun) {
                        $stats['dirs'] += $this->pruneEmptyDirs($fs, $top->path(), $items);
                    } elseif ($stats['kept'] + $stats['recent'] + $stats['errors'] === $survivors) {
                        $stats['dirs']++; // nothing in this presentation folder would survive
                    }
                }

                if (microtime(true) - $lastReport >= 30) {
                    $this->log(sprintf('  %s: %s files (%s) %s so far', $label, number_format($stats['deleted']),
                        File::format_size($stats['bytes']), $this->dryRun ? 'to delete' : 'deleted'));
                    $lastReport = microtime(true);
                }
            }

            $this->log(sprintf(
                '%s: %s %s files (%s) and %d empty folders; kept %s files in use, %s changed in the last %d h%s',
                $label,
                $this->dryRun ? 'would delete' : 'deleted',
                number_format($stats['deleted']),
                File::format_size($stats['bytes']),
                $stats['dirs'],
                number_format($stats['kept']),
                number_format($stats['recent']),
                $this->minAge / 3600,
                $stats['errors'] ? ", {$stats['errors']} errors" : ''
            ));
        }
    }

    protected function checkFile(Filesystem $fs, string $label, FileAttributes $file, array $keep, int $cutoff, array &$stats): void
    {
        $path = $file->path();
        if (str_starts_with(basename($path), '.') || $this->isInUse($path, $keep)) {
            $stats['kept']++;
            return;
        }
        if ((int) $file->lastModified() > $cutoff) {
            $stats['recent']++;
            return;
        }

        if ($stats['deleted'] < 5) {
            $this->log(sprintf('  %s %s:%s', $this->dryRun ? 'would delete' : 'delete', $label, $path));
        }
        if (!$this->dryRun) {
            try {
                $fs->delete($path);
            } catch (Throwable $e) {
                $stats['errors']++;
                $this->log("  $label:$path: {$e->getMessage()}");
                return;
            }
            if ($this->sleepMs && $stats['deleted'] % 500 === 499) {
                usleep($this->sleepMs * 1000);
            }
        }
        $stats['deleted']++;
        $stats['bytes'] += (int) $file->fileSize();
    }

    /**
     * A file is in use when it is the current or a kept version's file, or a variant of it:
     * hashed paths (.protected, older public files) must match name + hash, natural paths
     * the name. Paths SS can't parse (old _resampled folders) only match by their own name.
     */
    protected function isInUse(string $path, array $keep): bool
    {
        $hashed = HashFileIDHelper::singleton()->parseFileID($path);
        if ($hashed && isset($keep['hashed'][$hashed->getFilename() . '|' . $hashed->getHash()])) {
            return true;
        }
        $natural = NaturalFileIDHelper::singleton()->parseFileID($path);
        return isset($keep['names'][$natural ? $natural->getFilename() : $path]);
    }

    /**
     * Remove the directories under (and including) $dir that are empty now, deepest first.
     * Returns the number removed.
     *
     * @param array $items the deep listing of $dir taken before the files were cleaned
     */
    protected function pruneEmptyDirs(Filesystem $fs, string $dir, array $items): int
    {
        $dirs = [$dir];
        foreach ($items as $item) {
            if ($item->isDir()) {
                $dirs[] = $item->path();
            }
        }
        usort($dirs, fn($a, $b) => substr_count($b, '/') <=> substr_count($a, '/'));

        $removed = 0;
        foreach ($dirs as $path) {
            if (!$fs->has($path) || $fs->listContents($path)->toArray()) {
                continue;
            }
            try {
                $fs->deleteDirectory($path);
                $removed++;
            } catch (Throwable $e) {
                $this->log("  $path: {$e->getMessage()}");
            }
        }
        return $removed;
    }

    /**
     * Rebuild the version tables: InnoDB keeps the space of deleted rows until the table is
     * rebuilt. Runs online; after the cleanup the tables are small, so this is quick.
     */
    protected function optimizeTables(): void
    {
        $this->heading('Rebuild the _Versions tables (gives the disk space back)');
        $tables = $this->versionTables();
        if ($this->dryRun) {
            $this->log('Would run OPTIMIZE TABLE on ' . implode(', ', $tables));
            return;
        }

        // Give up instead of queueing every other query behind a waiting rebuild
        DB::query('SET SESSION lock_wait_timeout = 60');
        foreach ($tables as $table) {
            $start = microtime(true);
            try {
                DB::query("OPTIMIZE TABLE \"$table\"");
                $this->log("$table rebuilt in " . $this->duration(microtime(true) - $start));
            } catch (Throwable $e) {
                $this->log("$table: {$e->getMessage()}");
            }
        }
        $this->reportSizes($tables);
    }

    /**
     * Copies of the version tables (*_Versions_old) left by an earlier manual cleanup. Silverstripe
     * doesn't use them. Reported always, dropped with --drop-old.
     */
    protected function leftoverTables(bool $drop): void
    {
        $leftovers = [];
        foreach ($this->versionTables() as $table) {
            if (DB::get_schema()->hasTable($table . '_old')) {
                $leftovers[] = $table . '_old';
            }
        }
        if (!$leftovers) {
            return;
        }

        $this->heading('Leftover copies of the version tables');
        $this->reportSizes($leftovers);
        if (!$drop || $this->dryRun) {
            $this->log('Not used by Silverstripe. Add --drop-old (with --run) to drop them.');
            return;
        }
        foreach ($leftovers as $table) {
            DB::query("DROP TABLE \"$table\"");
            $this->log("Dropped $table");
        }
    }

    /**
     * Run $sql (with "BETWEEN %d AND %d") over the whole ID range of $table, batch by batch.
     * Only rows that exist when the step starts are touched.
     */
    protected function batchDelete(string $table, string $sql): int
    {
        $range = DB::query("SELECT MIN(\"ID\") AS \"Lo\", MAX(\"ID\") AS \"Hi\" FROM \"$table\"")->record();
        $lo = (int) $range['Lo'];
        $hi = (int) $range['Hi'];
        if (!$hi) {
            $this->log("$table is empty");
            return 0;
        }

        $batches = (int) ceil(($hi - $lo + 1) / $this->batchSize);
        $this->log(sprintf('%s: %s batches', $table, number_format($batches)));
        $deleted = 0;
        $start = $lastReport = microtime(true);
        for ($n = 1, $from = $lo; $from <= $hi; $n++, $from += $this->batchSize) {
            DB::query(sprintf($sql, $from, min($hi, $from + $this->batchSize - 1)));
            $deleted += DB::affected_rows();

            if (microtime(true) - $lastReport >= 30 || $n === $batches) {
                $elapsed = microtime(true) - $start;
                $this->log(sprintf(
                    '  %s: %d%%, %s rows deleted, %s',
                    $table,
                    $n / $batches * 100,
                    number_format($deleted),
                    $n === $batches ? 'done in ' . $this->duration($elapsed)
                        : '~' . $this->duration($elapsed / $n * ($batches - $n)) . ' left'
                ));
                $lastReport = microtime(true);
            }
            if ($this->sleepMs) {
                usleep($this->sleepMs * 1000);
            }
        }
        return $deleted;
    }

    /**
     * File names (and name + hash for hashed paths) still referenced under ovismedia/: the
     * current draft and live files, plus the history of non-OVIS files that live there.
     * OVIS photo history is left out on purpose, it is (being) deleted by the versions step.
     */
    protected function keepList(): array
    {
        $like = $this->quote($this->root . '/%');
        $classList = $this->quoteList(array_diff(
            ClassInfo::subclassesFor(File::class),
            ClassInfo::subclassesFor(PresentationMedia::class)
        ));

        $rows = DB::query(<<<SQL
            SELECT "FileFilename", "FileHash" FROM "File" WHERE "FileFilename" LIKE $like
            UNION
            SELECT "FileFilename", "FileHash" FROM "File_Live" WHERE "FileFilename" LIKE $like
            UNION
            SELECT "FileFilename", "FileHash" FROM "File_Versions"
                WHERE "ClassName" IN ($classList) AND "FileFilename" LIKE $like
            SQL);

        $keep = ['names' => [], 'hashed' => []];
        foreach ($rows as $row) {
            $keep['names'][$row['FileFilename']] = true;
            if ($row['FileHash']) {
                $hash = substr($row['FileHash'], 0, HashFileIDHelper::HASH_TRUNCATE_LENGTH);
                $keep['hashed'][$row['FileFilename'] . '|' . $hash] = true;
            }
        }
        return $keep;
    }

    /** Version tables of the photo class hierarchy, base table first */
    protected function versionTables(): array
    {
        $schema = DataObject::getSchema();
        $tables = [];
        foreach (ClassInfo::ancestry(PresentationMedia::class, true) as $class) {
            $table = $schema->tableName($class) . '_Versions';
            if (DB::get_schema()->hasTable($table)) {
                $tables[] = $table;
            }
        }
        return $tables;
    }

    protected function rootFolderID(): int
    {
        return (int) DB::prepared_query(
            'SELECT "ID" FROM "File" WHERE "ParentID" = 0 AND "Name" = ? AND "ClassName" = ?',
            [$this->root, Folder::class]
        )->value();
    }

    protected function reportSizes(array $tables): void
    {
        $list = $this->quoteList($tables);
        $rows = DB::query(<<<SQL
            SELECT TABLE_NAME AS "Name", TABLE_ROWS AS "Rows", DATA_LENGTH + INDEX_LENGTH AS "Size"
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ($list)
            SQL);
        foreach ($rows as $row) {
            $this->log(sprintf('  %s: ~%s rows, %s', $row['Name'], number_format((int) $row['Rows']),
                File::format_size((int) $row['Size'])));
        }
    }

    protected function quote(string $value): string
    {
        return DB::get_conn()->quoteString($value);
    }

    protected function quoteList(array $values): string
    {
        return implode(', ', array_map([$this, 'quote'], $values));
    }

    protected function heading(string $title): void
    {
        $this->output->writeln('');
        $this->log("== $title ==");
    }

    protected function log(string $message): void
    {
        $this->output->writeln('[' . date('H:i:s') . '] ' . $message);
    }

    protected function duration(float $seconds): string
    {
        $seconds = (int) round($seconds);
        if ($seconds >= 3600) {
            return sprintf('%dh %02dm', intdiv($seconds, 3600), intdiv($seconds % 3600, 60));
        }
        return $seconds >= 60 ? sprintf('%dm %02ds', intdiv($seconds, 60), $seconds % 60) : "{$seconds}s";
    }
}
