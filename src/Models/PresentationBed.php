<?php

namespace XD\Ovis\Models;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ValidationException;
use XD\Ovis\Schemas\PresentationSpecificationsBed;

/**
 * Class PresentationBed
 *
 * @author Bram de Leeuw
 * @package XD\Ovis\Models
 *
 * @property int Length
 * @property int Width
 * @property string Type
 */
class PresentationBed extends DataObject
{
    private static $table_name = 'Ovis_PresentationBed';

    private static $singular_name = 'Bed';

    private static $plural_name = 'Beds';

    private static $db = [
        'Length' => 'Int',
        'Width' => 'Int',
        'Type' => 'Varchar',
    ];

    private static $summary_fields = [
        'Type',
        'Length',
        'Width',
    ];

    public function getTitle()
    {
        return "{$this->Type} ({$this->Length} x {$this->Width})";
    }

    /**
     * @param PresentationSpecificationsBed $bed
     * @return DataObject|PresentationBed
     * @throws ValidationException
     */
    public static function findOrMake($bed)
    {
        $filter = array_filter([
            'Length' => $bed->length,
            'Width' => $bed->width,
            'Type' => $bed->type,
        ]);


        if (!$bed = self::get()->filter($filter)->first()) {
            $bed = self::create($filter);
            $bed->write();
        }

        return $bed;
    }
}
