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
    const ALL_CATEGORIES = 'All';

    private static $table_name = 'Ovis_OvisPage';

    private static $controller_name = OvisPageController::class;

    private static $db = array(
        'Category' => 'Varchar'
    );

    private static $defaults = array(
        'Category' => self::ALL_CATEGORIES
    );

    /**
     * Configure the filters you want to expose to your users
     * @var array
     */
    private static $filters = array(
        'Title',
        'Brand' => [
            'hasempty' => true
        ],
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
        'OvisCreated DESC' => 'Laatst Ingevoerd',
        'Title ASC' => 'Alfabetisch Oplopend',
        'Title DESC' => 'Alfabetisch Aflopend',
        'Price ASC' => 'Prijs Oplopend',
        'Price DESC' => 'Prijs Aflopend',
        'ConstructionYear DESC' => 'Bouwjaar'
    );

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $categories = Presentation::get()->column('Category');
        if (!empty($categories)) {
            $categories = array_map(fn($category) => ucwords($category ?? ''), array_filter(array_combine($categories, $categories)));
            $fields->addFieldsToTab('Root.Main', [
                DropdownField::create('Category', 'Category', $categories)
                    ->setEmptyString(_t(__CLASS__ . '.All', 'All'))
            ], 'Content');
        }

        return $fields;
    }
}
