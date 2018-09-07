<?php

/**
 * OVISPage
 * @property string Category
 */
class OvisPage extends Page
{
    private static $db = array(
        'Category' => 'Varchar(255)'
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
        $fields->addFieldsToTab('Root.Main', [
            TextField::create('Category', 'Category')
        ], 'Content');

        return $fields;
    }
}