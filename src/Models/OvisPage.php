<?php

namespace XD\Ovis\Models;

use Page;
use SilverStripe\Forms\DropdownField;
use XD\Ovis\Control\OvisPageController;
use XD\Ovis\Models\Presentation;


/**
 * OVISPage
 * @property string Category
 */
class OvisPage extends Page
{
    private static $table_name = 'Ovis_OvisPage';

    private static $controller_name = OvisPageController::class;

    private static $db = array(
        'Category' => 'Varchar'
    );

    private static $defaults = array(
        'Category' => 'All'
    );

    /**
     * Configure the filters you want to expose to your users
     * @var array
     */
    private static $filters = array(
        'Title',
        'Brand',
        'Price_From',
        'Price_Till',
        'ConstructionYear_From',
        'ConstructionYear_Till',
        'WeightMaximum_From',
        'WeightMaximum_Till',
        'NumberOfSleepingPlaces_From',
        'NumberOfSleepingPlaces_Till'
    );

    private static $sorting_options = array(
        'Title DESC' => 'Alfabetisch',
        'Price ASC' => 'Prijs Oplopend',
        'Price DESC' => 'Prijs Aflopend',
        'ConstructionYear DESC' => 'Bouwjaar',
        'OvisCreated DESC' => 'Laatst Ingevoerd'
    );

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $categories = Presentation::get()->column('Category');
        $categories = array_map('ucwords', array_combine($categories, $categories));
        $categories['All'] = 'All';
        $fields->addFieldsToTab('Root.Main', [
            DropdownField::create('Category', 'Category', $categories)
        ], 'Content');

        return $fields;
    }
}