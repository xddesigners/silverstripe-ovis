<?php

namespace XD\Ovis\Models;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\ORM\ValidationException;
use XD\Ovis\Schemas\PresentationSpecificationsAccessorySub;
use XD\Ovis\Schemas\PresentationSpecificationsSpecsDivision;

/**
 * Class PresentationAccessorySub
 *
 * @author Bram de Leeuw
 * @package XD\Ovis\Models
 *
 * @property string Label
 * @property string Units
 * @property string Value
 * @method ManyManyList Sub()
 */
class PresentationAccessorySub extends DataObject
{
    private static $table_name = 'Ovis_PresentationAccessorySub';

    private static $singular_name = 'Accessory sub section';

    private static $plural_name = 'Accessory sub sections';

    private static $db = [
        'Label' => 'Varchar',
        'Units' => 'Varchar',
        'Value' => 'Varchar',
    ];

    private static $summary_fields = [
        'Label',
        'Units',
        'Value',
    ];

    public function getTitle()
    {
        return trim(implode(' ', [
            $this->Label,
            $this->Units,
            $this->Value
        ]));
    }

    /**
     * @param PresentationSpecificationsAccessorySub $accessorySub
     * @return DataObject|PresentationAccessorySub
     * @throws ValidationException
     */
    public static function findOrMake($accessorySub)
    {
        $filter = array_filter([
            'Label' => $accessorySub->label,
            'Units' => $accessorySub->units,
            'Value' => $accessorySub->value
        ]);

        if (!$sub = self::get()->filter($filter)->first()) {
            $sub = self::create($filter);
            $sub->write();
        }

        return $sub;
    }
}
