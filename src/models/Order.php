<?php

namespace XD\Ovis\Models;

use DataObject;

/**
 * Class Order
 *
 * @author Bram de Leeuw
 * @package XD\Ovis\Models
 *
 * @property string Title
 */
class Order extends DataObject
{
    private static $table_name = 'Order';

    private static $singular_name = 'Order';

    private static $plural_name = 'Orders';

    private static $db = [
        'Name' => 'Varchar(255)',
        'Email' => 'Varchar(255)',
        'Phone' => 'Varchar(255)',
        'Address' => 'Varchar(255)',
        'PostalCode' => 'Varchar(255)',
        'Locality' => 'Varchar(255)',
        'Question' => 'Varchar(255)',
    ];

    private static $has_one = [
        'Presentation' => 'XD\Ovis\Models\Presentation'
    ];

    private static $summary_fields = [
        'Name',
        'Email',
        'Presentation.Title',
    ];

    public function getTitle()
    {
        return "New order by {$this->Name}";
    }
}
