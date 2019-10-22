<?php

namespace XD\Ovis\Models;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ManyManyList;
use XD\Ovis\Schemas\PresentationSpecificationsSpecsDivision;

/**
 * Class PresentationAccessory
 *
 * @author Bram de Leeuw
 * @package XD\Ovis\Models
 *
 * @property string Category
 * @property string Description
 *
 * @method Presentation Presentation()
 * @method ManyManyList Sub()
 */
class PresentationAccessory extends DataObject
{
    private static $table_name = 'Ovis_PresentationAccessory';

    private static $singular_name = 'Accessory';

    private static $plural_name = 'Accessories';

    private static $db = [
        'Category' => 'Varchar',
        'Description' => 'Varchar',
    ];

    private static $has_one = [
        'Presentation' => Presentation::class
    ];

    private static $many_many = [
        'Sub' => 'XD\Ovis\Models\PresentationAccessorySub'
    ];

    private static $summary_fields = [
        'Category',
        'Description',
        'Sub.Count' => 'Sub sections',
    ];

    public function getTitle()
    {
        return trim(implode(', ', [
            $this->Category,
            $this->Description
        ]));
    }

    protected function onBeforeDelete()
    {
        parent::onBeforeDelete();
        $this->Sub()->removeAll();
    }
}
