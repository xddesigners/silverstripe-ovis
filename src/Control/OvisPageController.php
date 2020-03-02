<?php

namespace XD\Ovis\Control;

use PageController;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\PaginatedList;
use SilverStripe\View\Requirements;
use XD\Ovis\Models\Order;
use XD\Ovis\Models\OvisPage;
use XD\Ovis\Models\Presentation;
use XD\Ovis\Ovis;

/**
 * Class OvisPageController
 * @mixin OvisPage
 *
 * @method OvisPage data
 */
class OvisPageController extends PageController
{
    private static $allowed_actions = array(
        'presentation',
        'Filters',
        'OrderForm'
    );

    public function presentation()
    {
        $slug = $this->getRequest()->param('ID');
        if ($this->presentation = DataObject::get_one(Presentation::class, ['Slug' => $slug])) {
            return $this->customise($this->presentation);
        } else {
            return $this->httpError(404, 'Not Found');
        }
    }

    /**
     * Create the filter interface
     * @return Form
     */
    public function Filters()
    {
        $request = $this->getRequest();
        $fields = FieldList::create();

        foreach (array_unique(OvisPage::config()->get('filters')) as $filter) {
            $filterParts = explode('_', $filter);
            $column = $filterParts[0];
            $value = $request->getVar($filter);
            $method = "get{$column}Values";

            if (self::hasMethod($method)) {
                $values = $this->$method();

                if (isset($filterParts[1]) && $filterParts[1] === 'Till') {
                    $value = $value ? $value : array_search(end($values), $values);
                } elseif (isset($filterParts[1]) && $filterParts[1] === 'From') {
                    $value = $value ? $value : array_search(current($values), $values);
                }

                $field = new DropdownField($filter, _t(__CLASS__ . ".Filter_$filter", $column), $values, $value);
            } else {
                $field = new TextField($filter, _t(__CLASS__ . ".Filter_$filter", $column), $request->getVar($filter));
            }

            $this->extend('updateFilterField', $field);
            $fields->add($field);
        }

        $sortingOptions = OvisPage::config()->get('sorting_options');

        $currentSortingValue = $request->getVar('Sort') ? $request->getVar('Sort') : array_search(current($sortingOptions), $sortingOptions);
        $sort = new DropdownField('Sort', _t('OvisPage.Sort', 'Sort'), $sortingOptions, $currentSortingValue);
        $fields->add($sort);

        $actions = FieldList::create(
            FormAction::create('searchovis')->setTitle(_t('OvisPage.Search', 'Search')),
            FormAction::create('reset')->setTitle(_t('OvisPage.Reset', 'Reset filters'))
        );

        $form = Form::create($this, 'Filters', $fields, $actions);
        $this->extend('updateFilterForm', $form);
        return $form;
    }

    /**
     * Handle the search filters
     * @param $data
     * @param Form $form
     */
    public function searchovis($data, Form $form)
    {
        unset($data['url']);
        unset($data['SecurityID']);
        unset($data['action_search']);
        if (empty($data['KeyWord'])) unset($data['KeyWord']);

        $query = http_build_query($data);
        $this->redirect($this->Link("?$query"));
    }

    /**
     * Reset the search filters
     * @param $data
     * @param Form $form
     */
    public function reset($data, Form $form)
    {
        $this->redirect($this->Link());
    }

    /**
     * Return the Occasions as a paginated list
     *
     * @return PaginatedList
     * @throws Exception
     */
    public function PaginatedPresentations()
    {
        $request = $this->getRequest();
        $sortingOptions = OvisPage::config()->get('sorting_options');
        $sort = self::sorting_column_exists($request->getVar('Sort'))
            ? $request->getVar('Sort')
            : array_search(current($sortingOptions), $sortingOptions);


        $filters = $this->getField('Category') != 'All'
            ? array('Category' => $this->Category)
            : array();

        foreach (array_unique(OvisPage::config()->get('filters')) as $filter) {
            if ($value = $request->getVar($filter)) {
                $filterParts = explode('_', $filter);
                $column = $filterParts[0];
                $fromTill = isset($filterParts[1]) ? $filterParts[1] : null;
                switch ($fromTill) {
                    case 'From':
                        $filters["$column:GreaterThanOrEqual"] = $value;
                        break;
                    case 'Till':
                        $filters["$column:LessThanOrEqual"] = $value;
                        break;
                    default:
                        $filters["$column:PartialMatch"] = $value;
                        break;
                }
            }
        }

        $this->extend('updateFilters', $filters);
        $occasions = Presentation::get()->filter($filters)->sort($sort);
        $paginatedList = PaginatedList::create($occasions, $this->getRequest());
        return $paginatedList->setPageLength(12);
    }

    /**
     * Check if the requested sorting is possible
     * @param $sort
     * @return bool
     */
    private static function sorting_column_exists($sort)
    {
        if (count(explode(' ', $sort)) === 2) {
            $sort = explode(' ', $sort);
            // FIX
            $fields = Presentation::config()->get('db');
            //$fields = DataObject::database_fields('XD\Ovis\Models\Presentation');

            $sortingColumnExists = array_key_exists($sort[0], $fields);
            $sortingOptionAvailable = array_key_exists($sort[1], array_flip(array('ASC', 'DESC')));

            return $sortingColumnExists && $sortingOptionAvailable;
        }

        return false;
    }

    /**
     * Get the distinct brands
     * @return array
     */
    public function getBrandValues()
    {
        $brands = Presentation::get()->sort('Brand DESC')->column('Brand');
        $brands = array_map('ucfirst', array_combine($brands, $brands));

        $brands[] = _t('OvisPage.Filter_AllBrands', 'All brands');
        $brands = array_reverse($brands, true);

        return $brands;
    }

    /**
     * Get the min and max prices based on the database
     * @return array
     */
    public function getPriceValues()
    {
        $min = Presentation::get()->min('Price');
        $max = Presentation::get()->max('Price');

        $minVal = (int)(floor($min / 100000) * 100000);
        $maxVal = (int)(ceil($max / 100000) * 100000);

        $values = array();
        while ($minVal <= $maxVal) {
            $price = number_format($minVal / 100, 2, ',', '.');
            $values[$minVal] = "€ $price";
            $minVal += 100000;
        }

        return $values;
    }

    /**
     * Get the available years based on the database
     * @return array
     */
    public function getConstructionYearValues()
    {
        $min = Presentation::get()->min('ConstructionYear');
        $max = Presentation::get()->max('ConstructionYear');

        $values = array();
        while ($min <= $max) {
            $values[$min] = $min;
            $min++;
        }
        
        return $values;
    }

    /**
     * Get the available weights based on the database
     * @return array
     */
    public function getWeightMaximumValues()
    {
        $min = Presentation::get()->min('WeightMaximum');
        $max = Presentation::get()->max('WeightMaximum');
        $minVal = (int)(floor($min / 100) * 100);
        $maxVal = (int)(ceil($max / 100) * 100);

        $values = array();
        while ($minVal <= $maxVal) {
            $weight = number_format($minVal, 0, ',', '.');
            $values[$minVal] = $weight;
            $minVal += 100;
        }

        return $values;
    }

    /**
     * Get the available beds based on the database
     * @return array
     */
    public function getNumberOfSleepingPlacesValues()
    {
        $min = Presentation::get()->min('NumberOfSleepingPlaces');
        $max = Presentation::get()->max('NumberOfSleepingPlaces');

        $values = array();
        while ($min <= $max) {
            $values[$min] = (string)$min;
            $min++;
        }

        return $values;
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
            TextareaField::create('Question', _t(__CLASS__ . '.Question', 'Additional questions')),
            HiddenField::create('PresentationID', 'PresentationID', $presentation->ID),

            DropdownField::create(
                'TradeIn',
                _t(__CLASS__ . '.TradeIn', 'Trade in'),
                Order::singleton()->getEnumValues('TradeIn')
            ),
            TextField::create('Brand', _t(__CLASS__ . '.Brand', 'Brand'))->addExtraClass('ovis-trade-field'),
            TextField::create('Model', _t(__CLASS__ . '.Model', 'Model'))->addExtraClass('ovis-trade-field'),
            DropdownField::create(
                'ConstructionYear',
                _t(__CLASS__ . '.ConstructionYear', 'Construction year'),
                array_combine($years = range(1969, date('Y')), $years)
            )->addExtraClass('ovis-trade-field'),
            DropdownField::create(
                'Condition',
                _t(__CLASS__ . '.Condition', 'Condition'),
                Order::singleton()->getEnumValues('Condition')
            )->addExtraClass('ovis-trade-field'),
            DropdownField::create(
                'Undamaged',
                _t(__CLASS__ . '.Undamaged', 'Undamaged'),
                Order::singleton()->getEnumValues('Undamaged')
            )->addExtraClass('ovis-trade-field'),
            DropdownField::create(
                'Upholstery',
                _t(__CLASS__ . '.Upholstery', 'Upholstery'),
                Order::singleton()->getEnumValues('Upholstery')
            )->addExtraClass('ovis-trade-field'),
            DropdownField::create(
                'Tires',
                _t(__CLASS__ . '.Tires', 'Tires'),
                Order::singleton()->getEnumValues('Tires')
            )->addExtraClass('ovis-trade-field')
        );

        $actions = FieldList::create(
            FormAction::create('Order', _t(__CLASS__ . '.Order', 'Order'))
        );

        $required = new RequiredFields(array('Name', 'Email'));
        $form = Form::create($this, 'OrderForm', $fields, $actions, $required);
        $this->extend('updateOrderForm', $form);

        Requirements::customScript(<<<JS
            var select = document.querySelector('select[name="TradeIn"]');
            function showFields() {
                var tradeFields = document.querySelectorAll('.field.ovis-trade-field');
                if (this.value && this.value !== 'Not') {
                    for (let tradeField of tradeFields) {
                        tradeField.style.display = 'block';
                    }
                } else {
                    for (let tradeField of tradeFields) {
                        tradeField.style.display = 'none';
                    }
                }
            }
            
            select.addEventListener('change', showFields);
            showFields();
JS
        );

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
        $this->extend('onBeforePlaceOrder', $order, $data, $form);

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