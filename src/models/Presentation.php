<?php

namespace XD\Ovis\Models;

use ArrayData;
use DataObject;
use Director;
use HasManyList;
use ManyManyList;
use SSViewer;
use URLSegmentFilter;

/**
 * Class Presentation
 *
 * @author Bram de Leeuw
 * @package XD\Ovis\Models
 *
 * @property string Title
 * @property string Slug
 * @property int OvisID
 * @property string OvisCreated
 * @property string OvisUpdated
 * @property string UserID
 * @property string ExternalAdID
 * @property string GUID
 * @property string Realm
 * @property string Locale
 * @property string Status
 * @property string Media360Link
 * @property string MediaPDFLink
 * @property string Media3DLink
 * @property string MediaVideoLink
 * @property string BannerAvekoLink
 * @property string BannerKampeerkredietLink
 * @property string BannerFinanplazaLink
 * @property string Category
 * @property string Brand
 * @property string Model
 * @property string Version
 * @property string TitleSuffix
 * @property string LicensePlate
 * @property string ChassisNumber
 * @property string Description
 * @property string Memo
 * @property boolean New
 * @property boolean Damaged
 * @property boolean Demo
 * @property boolean Classic
 * @property boolean Sold
 * @property boolean Expected
 * @property boolean Stock
 * @property boolean Reserved
 * @property boolean Outdated
 * @property boolean Rental
 * @property boolean ExRental
 * @property boolean Export
 * @property int ConstructionYear
 * @property int ConstructionMonth
 * @property int ModelYear
 * @property string DateArrival
 * @property string DatePart1a
 * @property string DatePurchased
 * @property boolean MediatorEnabled
 * @property string MediatorName
 * @property string MediatorPhoneNumber
 * @property string MediatorEmail
 * @property string MediatorDescription
 * @property int LengthConstruction
 * @property int LengthTotal
 * @property int Width
 * @property int Height
 * @property int Headroom
 * @property int WeightEmpty
 * @property int WeightOperational
 * @property int WeightMaximum
 * @property int Capacity
 * @property int NumberOfBeds
 * @property int NumberOfSleepingPlaces
 * @property int Bedrooms
 * @property boolean Bovag
 * @property string BovagEndDate
 * @property int BovagMonths
 * @property string BovagDescription
 * @property boolean Factory
 * @property string FactoryEndDate
 * @property int FactoryMonths
 * @property int FactoryMileage
 * @property string FactoryDescription
 * @property boolean MiscWarranty
 * @property string MiscEndDate
 * @property int MiscMonths
 * @property boolean MiscMileage
 * @property string MiscDescription
 * @property int Price
 * @property string PriceRetail
 * @property int PriceDisplay
 * @property int CostsRoadworthy
 * @property int PriceTakeout
 * @property int PriceTrade
 * @property int PriceExport
 * @property int PriceCatalogue
 * @property int PricePurchase
 * @property int PriceValuation
 * @property int PriceSold
 * @property string VAT
 *
 * @method HasManyList Media()
 * @method HasManyList Accessories()
 *
 * @method ManyManyList Beds()
 * @method ManyManyList Divisions()
 */
class Presentation extends DataObject
{
    private static $table_name = 'Presentation';

    private static $singular_name = 'Presentation';

    private static $plural_name = 'Presentations';

    private static $db = [
        'Title' => 'Varchar(255)',
        'Slug' => 'Varchar(255)',
        // XD\Ovis\Schemas\Presentation
        'OvisID' => 'Int',
        'OvisCreated' => 'SS_DateTime',
        'OvisUpdated' => 'SS_DateTime',
        'UserID' => 'Int',
        'ExternalAdID' => 'Int',
        'GUID' => 'Varchar(255)',
        'Realm' => 'Varchar(255)',
        'Locale' => 'Varchar(5)',
        'Status' => 'Varchar(255)',
        // XD\Ovis\Schemas\PresentationMedia
        'Media360Link' => 'Text',
        'MediaPDFLink' => 'Text',
        'Media3DLink' => 'Text',
        'MediaVideoLink' => 'Text',
        // XD\Ovis\Schemas\PresentationBanners
        'BannerAvekoLink' => 'Text',
        'BannerKampeerkredietLink' => 'Text',
        'BannerFinanplazaLink' => 'Text',
        // XD\Ovis\Schemas\PresentationSpecifications
        'Category' => 'Varchar(255)',
        'Brand' => 'Varchar(255)',
        'Model' => 'Varchar(255)',
        'Version' => 'Varchar(255)',
        'TitleSuffix' => 'Varchar(255)',
        'LicensePlate' => 'Varchar(255)',
        'ChassisNumber' => 'Varchar(255)',
        'Description' => 'HTMLText',
        'Memo' => 'Text',
        'New' => 'Boolean',
        'Damaged' => 'Boolean',
        'Demo' => 'Boolean',
        'Classic' => 'Boolean',
        'Sold' => 'Boolean',
        'Expected' => 'Boolean',
        'Stock' => 'Boolean',
        'Reserved' => 'Boolean',
        'Outdated' => 'Boolean',
        'Rental' => 'Boolean',
        'ExRental' => 'Boolean',
        'Export' => 'Boolean',
        // XD\Ovis\Schemas\PresentationSpecificationsDates
        'ConstructionYear' => 'Int',
        'ConstructionMonth' => 'Int',
        'ModelYear' => 'Int',
        'DateArrival' => 'SS_DateTime',
        'DatePart1a' => 'SS_DateTime',
        'DatePurchased' => 'SS_DateTime',
        // XD\Ovis\Schemas\PresentationSpecificationsMediator
        'MediatorEnabled' => 'Boolean',
        'MediatorName' => 'Varchar(255)',
        'MediatorPhoneNumber' => 'Varchar(255)',
        'MediatorEmail' => 'Varchar(255)',
        'MediatorDescription' => 'Varchar(255)',
        // XD\Ovis\Schemas\PresentationSpecificationsWeights
        'LengthConstruction' => 'Int',
        'LengthTotal' => 'Int',
        'Width' => 'Int',
        'Height' => 'Int',
        'Headroom' => 'Int',
        'WeightEmpty' => 'Int',
        'WeightOperational' => 'Int',
        'WeightMaximum' => 'Int',
        'Capacity' => 'Int',
        // XD\Ovis\Schemas\PresentationSpecificationsBeds
        'NumberOfBeds' => 'Int',
        'NumberOfSleepingPlaces' => 'Int',
        'Bedrooms' => 'Int',
        // XD\Ovis\Schemas\PresentationSpecificationsWarranty
        'Bovag' => 'Boolean',
        'BovagEndDate' => 'SS_DateTime',
        'BovagMonths' => 'Int',
        'BovagDescription' => 'Text',
        'Factory' => 'Boolean',
        'FactoryEndDate' => 'SS_DateTime',
        'FactoryMonths' => 'Int',
        'FactoryMileage' => 'Int',
        'FactoryDescription' => 'Text',
        'MiscWarranty' => 'Boolean',
        'MiscEndDate' => 'SS_DateTime',
        'MiscMonths' => 'Int',
        'MiscMileage' => 'Boolean',
        'MiscDescription' => 'Text',
        // XD\Ovis\Schemas\PresentationSpecificationPrices
        'Price' => 'Int',
        'PriceRetail' => 'Varchar(255)',
        'PriceDisplay' => 'Int',
        'CostsRoadworthy' => 'Int',
        'PriceTakeout' => 'Int',
        'PriceTrade' => 'Int',
        'PriceExport' => 'Int',
        'PriceCatalogue' => 'Int',
        'PricePurchase' => 'Int',
        'PriceValuation' => 'Int',
        'PriceSold' => 'Int',
        'VAT' => 'Varchar(255)',
        // TODO XD\Ovis\Schemas\PresentationSpecificationPricesRental
    ];

    private static $summary_fields = [
        'CMSThumbnail' => 'Thumbnail',
        'Title',
        'PriceNice' => 'Price',
        'Sold.Nice' => 'Sold'
    ];

    private static $indexes = [
        'Slug' => true,
        'OvisID' => true
    ];

    private static $has_many = [
        'Media' => 'XD\Ovis\Models\PresentationMedia',
        'Accessories' => 'XD\Ovis\Models\PresentationAccessory' // todo can this be a many_many join .. ?
    ];

    private static $many_many = [
        'Beds' => 'XD\Ovis\Models\PresentationBed',
        'Divisions' => 'XD\Ovis\Models\PresentationDivision'
    ];

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $this->Title = ucwords(trim(implode(' ', [
            $this->Brand,
            $this->Model,
            $this->Version
        ])));

        $segmentFilter = URLSegmentFilter::create();
        if (($slug = $segmentFilter->filter($this->Title)) && !self::get()->find('Slug', $slug)) {
            $this->Slug = $slug;
        } else {
            $this->Slug = $slug = $segmentFilter->filter("$this->ID $this->Title");;
        }
    }

    public function getMenuTitle()
    {
        return $this->Title;
    }

    public function Link()
    {
        if ($ovisPage = $this->getParent()) {
            return $ovisPage->Link("presentation/{$this->Slug}");
        }

        return null;
    }

    public function AbsoluteLink()
    {
        return Director::absoluteURL($this->Link());
    }

    protected function onBeforeDelete()
    {
        parent::onBeforeDelete();
        $this->Media()->removeAll();
        $this->Accessories()->removeAll();
        $this->Beds()->removeAll();
        $this->Divisions()->removeAll();
    }

    /**
     * Find or create a Ovis presentation based on the given OVIS ID
     *
     * @param $ovisID
     * @return DataObject|null|Presentation
     * @throws \ValidationException
     */
    public static function findOrMake($ovisID)
    {
        if (!$presentation = self::get()->find('OvisID', $ovisID)) {
            $presentation = self::create();
            $presentation->OvisID = $ovisID;
            $presentation->write();
        }

        return $presentation;
    }

    /**
     * Get the default Image
     *
     * @return PresentationMedia
     */
    public function getDefaultImage()
    {
        /** @var $image PresentationMedia */
        if (($image = $this->Media()->find('Default', true)) && $image->exists()) {
            return $image;
        } elseif (($image = $this->Media()->first()) && $image->exists()) {
            return $image;
        }

        return PresentationMedia::singleton();
    }

    public function CMSThumbnail()
    {
        if ($image = $this->getDefaultImage()){
            return $image->CMSThumbnail();
        }

        return null;
    }

    function getOGImage()
    {
        if ($image = $this->getDefaultImage()){
            return $image->Fill(1200, 630);
        }

        return null;
    }

    function getOGDescription()
    {
        return $this->Description;
    }

    /**
     * Return the formatted price
     *
     * @return string
     */
    public function PriceRetailNice()
    {
        $price = number_format((int)$this->PriceRetail / 100, 2, ',', '.');
        return "€ $price";
    }

    /**
     * Return the formatted price
     *
     * @return string
     */
    public function PriceNice()
    {
        $price = number_format((int)$this->Price / 100, 2, ',', '.');
        return "€ $price";
    }

    /**
     * Return a Ovis Page instance
     *
     * @return DataObject|\OvisPage
     */
    public function getParent()
    {
        if ($page = DataObject::get_one('OvisPage', ['Category' => $this->Category])) {
            return $page;
        } elseif ($page = DataObject::get_one('OvisPage')) {
            return $page;
        }

        return null;
    }

    /**
     * Make compatible with breadcrumb templates
     * @see \SiteTree::getBreadcrumbs()
     *
     * @return \HTMLText
     */
    public function getBreadCrumbs()
    {
        $parent = $this->getParent();
        $pages = $parent->getBreadcrumbItems(20, false, false);
        $pages->add($this);
        $template = new SSViewer('BreadcrumbsTemplate');
        return $template->process($parent->customise(new ArrayData(array(
            'Pages' => $pages
        ))));
    }

    /**
     * Return presentations from the same brand
     *
     * @param int $limit
     * @return \DataList
     */
    public function getSimilarPresentations($limit = 8)
    {
        return Presentation::get()
            ->filter('Brand', $this->Brand)
            ->exclude('ID', $this->ID)
            ->sort("RAND()")
            ->limit($limit);
    }
}
