<?php

namespace XD\Ovis\Models;

use DataObject;
use Email;

/**
 * Class Order
 *
 * @author Bram de Leeuw
 * @package XD\Ovis\Models
 *
 * @property string Name
 * @property string Email
 * @property string Phone
 * @property string Address
 * @property string PostalCode
 * @property string Locality
 * @property string Question
 * @method Presentation Presentation()
 */
class Order extends DataObject
{
    private static $table_name = 'Order';

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
        'Name' => 'Varchar(255)',
        'Email' => 'Varchar(255)',
        'Phone' => 'Varchar(255)',
        'Address' => 'Varchar(255)',
        'PostalCode' => 'Varchar(255)',
        'Locality' => 'Varchar(255)',
        'Question' => 'Varchar(255)',
    ];

    private static $default_sort = 'Created DESC';

    private static $has_one = [
        'Presentation' => 'XD\Ovis\Models\Presentation'
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
            $to = Email::config()->get('admin_to');
        }

        return Email::create($from, $to, $this->getTitle())
            ->setReplyTo($this->Email)
            ->setTemplate('OrderEmail')
            ->populateTemplate($this);
    }
}
