# OVIS module for Silverstripe
This module adds an import for [OVIS](https://www.ovis.nl/) presentations.
 
## Installation
Install the module trough composer and configure it with your search parameters, see the docs for all available search params.  
`composer require xddesigners/silverstripe-ovis`

### Basic config
```yaml
XD\Ovis\Ovis:
  api_key: 'YOUR_API_KEY'
  search:
    category:
      - mobilehome
      - tenttrailer
      - caravan
      - camper

XD\Ovis\Models\Order:
  email_from: 'YOUR_FROM_ADDRESS' # defaults to Email.admin_email
  email_to: 'YOUR_TO_ADDRESS' # defaults to Email.admin_email
```

### Set up import script
You can run the import script manually trough the dev/tasks interface or set up up to run as a cron task. 
`http://example.com/dev/tasks/XD-Ovis-Tasks-Import` or `sake dev/tasks/XD-Ovis-Tasks-Import`

### Clean up old photos and version history
`XD\Ovis\Tasks\Cleanup` removes what older imports left behind: photos whose presentation is gone, the version
history of every photo (the current draft and live version are kept), empty `ovismedia/` folders and files on disk
that no record uses. It then rebuilds the `_Versions` tables so the database gives the disk space back.

Command line and MySQL / MariaDB only. It reports what it would delete unless you tell it to run:
```bash
vendor/bin/sake tasks:ovis-cleanup          # dry run
vendor/bin/sake tasks:ovis-cleanup --run
```
Options: `--steps=media,versions,folders,files,optimize`, `--batch=5000` (version rows per delete), `--sleep=100` (ms
between deletes), `--min-age=24` (hours, newer files are never deleted) and `--drop-old` (drop `*_Versions_old` table copies).

Make a database backup first. Deletes run in small batches with a pause in between, so the site stays up, but the first
run can take hours on a big site: start it with `nohup … &`. It is safe to stop and run again.
