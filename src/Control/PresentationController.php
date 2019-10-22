<?php

namespace XD\Ovis\Control;

use Exception;
use PageController;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;
use XD\Ovis\Models\Order;
use XD\Ovis\Models\Presentation;

/**
 * Class PresentationController
 * @property Presentation dataRecord
 */
class PresentationController extends PageController
{
    private static $allowed_actions = [
        'presentation',
        'OrderForm'
    ];

    /**
     * Store the presentation object outside of dataRecord
     * @var Presentation
     */
    protected $presentation;

    /**
     *
     */
    public function index()
    {
        $this->httpError(404, 'Not Found');
    }

    public function presentation()
    {
        $slug = $this->getRequest()->param('ID');
        if ($this->presentation = DataObject::get_one(Presentation::class, ['Slug' => $slug])) {
            return $this->customise($this->presentation)->renderWith(['OvisPresentation', 'Page']);
        } else {
            return $this->httpError(404, 'Not Found');
        }
    }

    /**
     * Create a direct order form
     *
     * @return Form
     */
    public function OrderForm()
    {
        $slug = $this->getRequest()->param('ID');
        if (!$presentation = $this->presentation) {
            $presentation = DataObject::get_one(Presentation::class, ['Slug' => $slug]);
        }

        $fields = FieldList::create(
            TextField::create('Name', _t(__CLASS__ . '.Name', 'Name')),
            EmailField::create('Email', _t(__CLASS__ . '.Email', 'Email')),
            TextField::create('Phone', _t(__CLASS__ . '.Phone', 'Phone')),
            TextField::create('Address', _t(__CLASS__ . '.Address', 'Address')),
            TextField::create('PostalCode', _t(__CLASS__ . '.PostalCode', 'Postal code')),
            TextField::create('Locality', _t(__CLASS__ . '.Locality', 'Locality')),
            TextareaField::create('Question', _t(__CLASS__ . '.Question', 'Additional questions')),
            HiddenField::create('PresentationID', 'PresentationID', $presentation->ID)
        );

        $actions = FieldList::create(
            FormAction::create('Order', _t('OvisOrderForm.Order', 'Order'))
        );

        $required = new RequiredFields(array('Name', 'Email'));
        $form = Form::create($this, 'OrderForm', $fields, $actions, $required);
        $this->extend('updateOrderForm', $form);
        return $form;
    }

    /**
     * Submit and save the order
     *
     * @param $data
     * @param Form $form
     * @throws Exception
     */
    public function Order($data, Form $form)
    {
        $order = Order::create();
        $form->saveInto($order);

        try {
            $order->write();
            $order->createEmail()->send();
            $form->sessionMessage(
                _t(__CLASS__ . '.Success', 'Thank you for your order'),
                'good'
            );
        } catch (Exception $e) {
            $form->sessionMessage(
                _t(__CLASS__ . '.Error', 'Something when wrong while placing your order, please contact the store'),
                'bad'
            );
        }

        $this->redirectBack();
    }
}
