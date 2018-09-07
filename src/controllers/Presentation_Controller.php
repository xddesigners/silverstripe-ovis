<?php

//namespace XD\Ovis\Control;


use XD\Ovis\Models\Order;
use XD\Ovis\Models\Presentation;

/**
 * Class PresentationController
 * @property Presentation dataRecord
 */
class Presentation_Controller extends Page_Controller
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
        if ($this->presentation = DataObject::get_one('XD\Ovis\Models\Presentation', ['Slug' => $slug])) {
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
        $fields = FieldList::create(
            TextField::create('Name', _t('OvisOrderForm.Name', 'Name')),
            EmailField::create('Email', _t('OvisOrderForm.Email', 'Email')),
            TextField::create('Phone', _t('OvisOrderForm.Phone', 'Phone')),
            TextField::create('Address', _t('OvisOrderForm.Address', 'Address')),
            TextField::create('PostalCode', _t('OvisOrderForm.PostalCode', 'Postal code')),
            TextField::create('Locality', _t('OvisOrderForm.Locality', 'Locality')),
            TextareaField::create('Question', _t('OvisOrderForm.Question', 'Additional questions'))
        );

        if ($presentation = $this->presentation) {
            $fields->push(HiddenField::create('PresentationID', 'PresentationID', $presentation->ID));
        }

        $actions = FieldList::create(
            FormAction::create('Order', _t('OvisOrderForm.Order', 'Order'))
        );

        $required = new RequiredFields(array('Name', 'Email'));
        return Form::create($this, 'OrderForm', $fields, $actions, $required);
    }

    public function Order($data, Form $form)
    {
        $order = Order::create();
        $form->saveInto($order);

        try {
            $order->write();
            $form->sessionMessage(
                _t('OvisOrderForm.Success', 'Thank you for your order'),
                'good'
            );
        } catch (Exception $e) {
            $form->sessionMessage(
                _t('OvisOrderForm.Error', 'Something when wrong while placing your order, please contact the store'),
                'bad'
            );
        }

        $this->redirectBack();
    }
}
