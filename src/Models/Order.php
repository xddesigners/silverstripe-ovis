<?php

namespace XD\Ovis\Models;

use SilverStripe\ORM\DataObject;
use SilverStripe\Control\Email\Email;

/**
 * Class Order
 *
 * @author Bram de Leeuw
 * @package XD\Ovis\Models
 *
 * @property string Name
 * @property string Email
 * @property string Phone
 * @property string Question
 * @property string TradeIn
 * @property string Brand
 * @property string Model
 * @property string ConstructionYear
 * @property string Condition
 * @property string Undamaged
 * @property string Upholstery
 * @property string Tires
 *
 * @method Presentation Presentation()
 */
class Order extends DataObject
{
    private static $table_name = 'Ovis_Order';

    private static $singular_name = 'Order';

    private static $plural_name = 'Orders';

    /**
     * Set an address to send email from
     * Defaults to admin_email
     *
     * @var null
     */
    private static $email_from = null;

    /**
     * Set an address to send email to
     * Defaults to admin_email
     *
     * @var null
     */
    private static $email_to = null;

    private static $db = [
        'Name' => 'Varchar',
        'Email' => 'Varchar',
        'Phone' => 'Varchar',
        'Question' => 'Varchar',
        'TradeIn' => 'Enum("Not,Caravan,Camper,Other", "Not")',
        'Brand' => 'Varchar',
        'Model' => 'Varchar',
        'ConstructionYear' => 'Varchar(4)',
        'Condition' => 'Enum("Mint,Good,Reasonable,Damaged", "Mint")',
        'Undamaged' => 'Enum("Yes,No", "Yes")',
        'Upholstery' => 'Enum("VeryGood,Good,Reasonable,Bad,VeryBad", "VeryGood")',
        'Tires' => 'Enum("VeryGood,Good,Reasonable,Bad,VeryBad", "VeryGood")',
    ];

    private static $default_sort = 'Created DESC';

    private static $has_one = [
        'Presentation' => Presentation::class
    ];

    private static $summary_fields = [
        'Name',
        'Email',
        'Presentation.Title' => 'About',
        'Created.Nice' => 'Date',
    ];

    public function getTitle()
    {
        return _t(
            'OvisOrder.Subject',
            'New order by {name} for {presentation}',
            null, [
                'name' => $this->Name,
                'presentation' => $this->Presentation()->getTitle()
            ]
        );
    }

    /**
     * Create the email to send
     *
     * @return Email
     */
    public function createEmail()
    {
        if (!$from = self::config()->get('email_from')) {
            $from = Email::config()->get('admin_email');
        }

        if (!$to = self::config()->get('email_to')) {
            $to = Email::config()->get('admin_email');
        }

        $email = Email::create($from, $to, $this->getTitle())
            ->setReplyTo($this->Email)
            ->setHTMLTemplate('XD\Ovis\Email\OrderEmail')
            ->setData($this);

        return $email;
    }

    public function getEnumValues($column)
    {
        if (($dbObject = $this->dbObject($column)) && $dbObject->hasMethod('enumValues')) {
            return array_map(function ($option) use ($column) {
                return _t(Order::class . ".{$column}_{$option}", $option);
            }, $dbObject->enumValues());
        }

        return [];
    }


    public function TradeInNice(){
        return _t(__CLASS__.'TradeIn_'.$this->TradeIn,$this->TradeIn);
    }

    public function ConditionNice(){
        return _t(__CLASS__.'.Condition_'.$this->Condition,$this->Condition);
    }

    public function UpholsteryNice(){
        return _t(__CLASS__.'.Upholstery_'.$this->Upholstery,$this->Upholstery);
    }

    public function TiresNice(){
        return _t(__CLASS__.'.Tires_'.$this->Tires,$this->Tires);
    }

    public function UndamagedNice(){
        return _t(__CLASS__.'.Undamaged_'.$this->Undamaged,$this->Undamaged);
    }

}
