<?php

namespace XD\Ovis\Models;

use DataObject;
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
    private static $table_name = 'PresentationDivision';

    private static $singular_name = 'Division';

    private static $plural_name = 'Divisions';

    private static $db = [
        'Category' => 'Varchar(255)',
        'Division' => 'Varchar(255)',
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
     * @return PresentationDivision
     * @throws \ValidationException
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
