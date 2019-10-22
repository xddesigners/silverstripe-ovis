<?php

namespace XD\Ovis\Models;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ValidationException;
use XD\Ovis\Schemas\PresentationSpecificationsSpecsDivision;

/**
 * Class PresentationDivision
 *
 * @author Bram de Leeuw
 * @package XD\Ovis\Models
 *
 * @property string Category
 * @property string Division
 */
class PresentationDivision extends DataObject
{
    private static $table_name = 'Ovis_PresentationDivision';

    private static $singular_name = 'Division';

    private static $plural_name = 'Divisions';

    private static $db = [
        'Category' => 'Varchar',
        'Division' => 'Varchar',
    ];

    private static $summary_fields = [
        'Category',
        'Division',
    ];

    public function getTitle()
    {
        return trim(implode(', ', [
            $this->Category,
            $this->Division
        ]));
    }

    /**
     * @param PresentationSpecificationsSpecsDivision $division
     * @return DataObject|PresentationDivision
     * @throws ValidationException
     */
    public static function findOrMake($division)
    {
        $filter = array_filter([
            'Category' => $division->category,
            'Division' => $division->division,
        ]);

        if (!$division = self::get()->filter($filter)->first()) {
            $division = self::create($filter);
            $division->write();
        }

        return $division;
    }
}
