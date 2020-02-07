<?php

use XD\Ovis\Models\Presentation;
use XD\Ovis\Ovis;

/**
 * Class OvisPage_controller
 * @mixin OvisPage
 *
 * @method OvisPage data
 */
class OvisPage_controller extends page_controller
{
    private static $allowed_actions = array(
        'Filters','presentation','OrderForm'
    );

    public function init()
    {
        parent::init();
    }

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

        foreach (OvisPage::config()->get('filters') as $filter) {
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

                $field = new DropdownField($filter, _t("OvisPage.Filter_$filter", $column), $values, $value);
            } else {
                $field = new TextField($filter, _t("OvisPage.Filter_$filter", $column), $request->getVar($filter));
            }

            $this->extend('updateFilterField', $field);
            $fields->add($field);

        }
        $sortingOptions = OvisPage::config()->get('sorting_options');

        $currentSortingValue = $request->getVar('Sort') ? $request->getVar('Sort') : array_search(current($sortingOptions), $sortingOptions);
        $sort = new DropdownField('Sort', _t('OvisPage.Sort', 'Sort'), $sortingOptions, $currentSortingValue);
        $fields->add($sort);

        $actions = FieldList::create(
            FormAction::create('search')->setTitle(_t('OvisPage.Search', 'Search')),
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
    public function search($data, Form $form)
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

        foreach (OvisPage::config()->get('filters') as $filter) {
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
            $fields = DataObject::database_fields('XD\Ovis\Models\Presentation');
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
        $brands = Presentation::get()->sort('Brand ASC')->column('Brand');
        $brands = array_map('ucfirst', array_combine($brands, $brands));
        array_unshift($brands, _t('OvisPage.Filter_AllBrands', 'All brands'));
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
            $presentation = DataObject::get_one('XD\Ovis\Models\Presentation', ['Slug' => $slug]);
        }

        $fields = FieldList::create(
            TextField::create('Name', _t('OvisOrderForm.Name', 'Name')),
            EmailField::create('Email', _t('OvisOrderForm.Email', 'Email')),
            TextField::create('Phone', _t('OvisOrderForm.Phone', 'Phone')),
            TextField::create('Address', _t('OvisOrderForm.Address', 'Address')),
            TextField::create('PostalCode', _t('OvisOrderForm.PostalCode', 'Postal code')),
            TextField::create('Locality', _t('OvisOrderForm.Locality', 'Locality')),
            TextareaField::create('Question', _t('OvisOrderForm.Question', 'Additional questions')),
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
     */
    public function Order($data, Form $form)
    {
        $order = Order::create();
        $form->saveInto($order);

        try {
            $order->write();
            $order->createEmail()->send();
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