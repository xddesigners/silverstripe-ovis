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
 * @property string Address
 * @property string PostalCode
 * @property string Locality
 * @property string Question
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
        'Address' => 'Varchar',
        'PostalCode' => 'Varchar',
        'Locality' => 'Varchar',
        'Question' => 'Varchar',
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

        return Email::create($from, $to, $this->getTitle())
            ->setReplyTo($this->Email)
            ->setTemplate('XD\Ovis\Email\OrderEmail')
            ->populateTemplate($this);
    }
}
