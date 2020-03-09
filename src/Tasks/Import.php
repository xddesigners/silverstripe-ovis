<?php

namespace XD\Ovis\Tasks;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use SilverStripe\Control\Director;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\DataObject;
use SilverStripe\Assets\Folder;
use XD\Ovis\Models\PresentationAccessory;
use XD\Ovis\Models\PresentationAccessorySub;
use XD\Ovis\Models\PresentationBed;
use XD\Ovis\Models\PresentationDivision;
use XD\Ovis\Models\PresentationMedia;
use XD\Ovis\Ovis;
use XD\Ovis\Models\Presentation;
use XD\Ovis\Schemas\PresentationMediaImage;
use XD\Ovis\Schemas\PresentationSpecificationsAccessory;
use XD\Ovis\Schemas\PresentationSpecificationsBed;
use XD\Ovis\Schemas\PresentationSpecificationsSpecsDivision;
use XD\Ovis\Schemas\SearchResponse;

/**
 * Class Import
 *
 * @author Bram de Leeuw
 * @package XD
 * @subpackage Ovis
 */
class Import extends BuildTask
{
    const NOTICE = 0;
    const SUCCESS = 1;
    const WARN = 2;
    const ERROR = 3;

    protected $title = 'Import the OVIS data';

    protected $description = 'Import the OVIS data';

    protected $enabled = true;
    
    private static $use_clean_images = false;

    /**
     * Define the data mapping for the importable object
     * @var array
     */
    private static $data_mapping = [
        'id' => 'OvisID',
        'created' => 'OvisCreated',
        'updated' => 'OvisUpdated',
        'userId' => 'UserID',
        'externalAdId' => 'ExternalAdID',
        'guid' => 'GUID',
        'realm' => 'Realm',
        'locale' => 'Locale',
        'status' => 'Status',
        'mediainfo' => [
            '360' => [
                'url' => 'Media360Link'
            ],
            'pdf' => 'MediaPDFLink',
            '3D' => 'Media3DLink',
            'taGGleVideo' => 'MediaVideoLink'
        ],
        'banners' => [
            'aveko' => [
                'url' => 'BannerAvekoLink'
            ],
            'kampeerkrediet' => [
                'url' => 'BannerKampeerkredietLink'
            ],
            'finanplaza' => [
                'url' => 'BannerFinanplazaLink'
            ]
        ],
        'specifications' => [
            'category' => 'Category',
            'brand' => 'Brand',
            'model' => 'Model',
            'version' => 'Version',
            'titleSuffix' => 'TitleSuffix',
            'licensePlate' => 'LicensePlate',
            'chassisNumber' => 'ChassisNumber',
            'description' => 'Description',
            'memo' => 'Memo',
            'new' => 'New',
            'damaged' => 'Damaged',
            'demo' => 'Demo',
            'classic' => 'Classic',
            'sold' => 'Sold',
            'expected' => 'Expected',
            'stock' => 'Stock',
            'reserved' => 'Reserved',
            'outdated' => 'Outdated',
            'rental' => 'Rental',
            'exRental' => 'ExRental',
            'export' => 'Export',
            'dates' => [
                'constructionYear' => 'ConstructionYear',
                'constructionMonth' => 'ConstructionMonth',
                'modelYear' => 'ModelYear',
                'dateArrival' => 'DateArrival',
                'datePart1a' => 'DatePart1a',
                'datePurchased' => 'DatePurchased'
            ],
            'mediator' => [
                'enabled' => 'MediatorEnabled',
                'name' => 'MediatorName',
                'phoneNumber' => 'MediatorPhoneNumber',
                'email' => 'MediatorEmail',
                'description' => 'MediatorDescription'
            ],
            'weightsMeasures' => [
                'lengthConstruction' => 'LengthConstruction',
                'lengthTotal' => 'LengthTotal',
                'width' => 'Width',
                'height' => 'Height',
                'headroom' => 'Headroom',
                'weightEmpty' => 'WeightEmpty',
                'weightOperational' => 'WeightOperational',
                'weightMaximum' => 'WeightMaximum',
                'capacity' => 'Capacity'
            ],
            'beds' => [
                'numberOfBeds' => 'NumberOfBeds',
                'numberOfSleepingPlaces' => 'NumberOfSleepingPlaces',
                'bedrooms' => 'Bedrooms',
            ],
            'warranty' => [
                'bovag' => 'Bovag',
                'bovagEndDate' => 'BovagEndDate',
                'bovagMonths' => 'BovagMonths',
                'bovagDescription' => 'BovagDescription',
                'factory' => 'Factory',
                'factoryEndDate' => 'FactoryEndDate',
                'factoryMonths' => 'FactoryMonths',
                'factoryMileage' => 'FactoryMileage',
                'factoryDescription' => 'FactoryDescription',
                'miscWarranty' => 'MiscWarranty',
                'miscEndDate' => 'MiscEndDate',
                'miscMonths' => 'MiscMonths',
                'miscMileage' => 'MiscMileage',
                'miscDescription' => 'MiscDescription'
            ],
            'prices' => [
                'price' => 'Price',
                'retail' => 'PriceRetail',
                'display' => 'PriceDisplay',
                'costsRoadworthy' => 'CostsRoadworthy',
                'takeout' => 'PriceTakeout',
                'trade' => 'PriceTrade',
                'export' => 'PriceExport',
                'catalogue' => 'PriceCatalogue',
                'purchase' => 'PricePurchase',
                'valuation' => 'PriceValuation',
                'sold' => 'PriceSold',
                'vat' => 'VAT',
            ],
            'pricesRental' => [
                'price3Hours' => 'RentalPrice3Hours',
                'priceDayPart' => 'RentalPriceDayPart',
                'priceDay' => 'RentalPriceDay',
                'priceWeekend' => 'RentalPriceWeekend',
                'priceWeek' => 'RentalPriceWeek',
                'priceMonth' => 'RentalPriceMonth',
                'deposit' => 'RentalDeposit',
                'noclaim' => 'RentalNoClaim'
            ]
        ]
    ];

    private $oldManifest = [];
    private $newManifest = [];

    public function run($request)
    {
        // Set the current state of presentations
        $this->oldManifest = Presentation::get()->map('ID', 'OvisID')->toArray();

        // Do the search
        $this->search();

        // Check what presentations to delete
        $toDeleteItems = array_diff($this->oldManifest, $this->newManifest);
        foreach ($toDeleteItems as $id => $ovisId) {
            DataObject::delete_by_id(Presentation::class, $id);
            self::log("[DELETED] presentation $id", self::NOTICE);
        }

        self::log('Finished: no pages left to query', self::SUCCESS);
        exit(self::SUCCESS);
    }

    public function search($page = 1)
    {
        try {
            $result = Ovis::search(['itemsPerPage' => 100, 'page' => $page]);
        } catch (GuzzleException $e) {
            self::log($e->getMessage(), self::ERROR);
            self::log('Could not parse the OVIS API', self::ERROR);
            exit(self::ERROR);
        } catch (Exception $e) {
            self::log('No search query is set', self::ERROR);
            exit(self::ERROR);
        }

        /** @var SearchResponse $contents */
        if (($body = $result->getBody()) && ($contents = Convert::json2obj($body->getContents()))) {
            if (!$contents->result) {
                self::log('No search result', self::NOTICE);
                exit(self::NOTICE);
            }

            $searchResponseDescription = $contents->data;
            foreach ($searchResponseDescription->data as $item) {
                if ($presentation = $this->importPresentation($item->presentation)) {
                    $this->newManifest[$presentation->ID] = $presentation->OvisID;
                }
            }

            if ($searchResponseDescription->totalInSet === $searchResponseDescription->itemsPerPage) {
                $this->search(($page + 1));
            }
        }
    }

    /**
     * Import the presentation object
     *
     * @param \XD\Ovis\Schemas\Presentation $presentation
     * @return Presentation|null
     */
    public function importPresentation($presentation)
    {

        try {
            $importObj = Presentation::findOrMake($presentation->id);
            $dataMapping = self::config()->get('data_mapping');

            // Set the object data
            self::loop_map($dataMapping, $importObj, $presentation);

            // Import the images
            if (($images = $presentation->mediainfo->images) && is_array($images)) {
                $importedImages = [];
                $isFirst = true;
                if( self::config()->get('use_clean_images') ) {
                    $isFirst = false;
                }
                foreach ($images as $image) {
                    $media = self::importMedia($image, $importObj, $isFirst);
                    $importedImages[] = $media->Name;
                    $isFirst = false;
                }

                // Check if there is old media to delete
                $toDeleteMedia = $importObj->Media()->exclude(['Name' => $importedImages]);
                $toDeleteMedia->removeAll();
            }

            // Beds
            if (($beds = $presentation->specifications->beds->bedSpecifications) && is_array($beds)) {
                foreach ($beds as $bed) {
                    self::importBed($bed, $importObj);
                }
            }

            // Lay-out divisions
            if (
                ($specs = $presentation->specifications->specsCaravan) &&
                ($divisions = $specs->division) &&
                is_array($divisions)
            ) {
                foreach ($divisions as $division) {
                    self::importDivision($division, $importObj);
                }
            }

            // Accessories
            if (($accessories = $presentation->specifications->accessories) && is_array($accessories)) {
                foreach ($accessories as $accessory) {
                    self::importAccessories($accessory, $importObj);
                }
            }

            $importObj->write();
            self::log("[Presentation][{$importObj->ID}] Created presentation {$importObj->getTitle()}", self::SUCCESS);
            return $importObj;
        } catch (Exception $e) {
            self::log($e->getMessage(), self::ERROR);
        }

        return null;
    }

    /**
     * Loop the given data map and possible sub maps
     *
     * @param array $map
     * @param Presentation $object
     * @param \XD\Ovis\Schemas\Presentation $data
     */
    private static function loop_map($map, &$object, $data)
    {
        foreach ($map as $from => $to) {
            if (is_array($to) && is_object($data->{$from})) {
                self::loop_map($to, $object, $data->{$from});
            } elseif ($value = $data->{$from}) {
                if (is_object($value)) {
                    self::log("Unconfigured value {$from}", self::ERROR);;
                } else {
                    $object->{$to} = $value;
                }
            }
        }
    }

    /**
     * Import and attach media files
     *
     * @param $image
     * @param Presentation $presentation
     * @return PresentationMedia
     */
    private static function importMedia($image, Presentation $presentation, $isFirst)
    {
        if( self::config()->get('use_clean_images') ) {
            // always import clean images without labels
            $url = $image->traditional->original->clean->url;
        } else {
            if( $isFirst ) {
                // first image imported with labels included
                $url = $image->traditional->original->default->url;
            } else {
                // other images imported as clean image
                $url = $image->traditional->original->clean->url;
            }
        }
        self::log($url,self::NOTICE);

        $urlInfo = parse_url($url);
        $urlPath = $urlInfo['path'];
        $exploded = array_filter(explode('/', $urlPath));
        $fileName = array_shift($exploded);
        if($isFirst){
            // add to name
            $fileName = str_replace('.jpg','_label.jpg',$fileName);
        }
        $slug = $presentation->Slug ?: $presentation->ID;
        $folderPath = 'ovismedia/' . $slug;

        self::log($fileName,self::NOTICE);


        /** @var PresentationMedia $media */
        if (!$media = $presentation->Media()->find('Name', $fileName)) {
            $media = PresentationMedia::create();
            try {
                $media->downloadImageTo($url, $fileName, $folderPath);
                $media->generateThumbnails();
            } catch (GuzzleException $e) {
                self::log("[PresentationMedia][Download] {$e->getMessage()}", self::ERROR);
            } catch (Exception $e) {
                self::log("[PresentationMedia][Download] {$e->getMessage()}", self::ERROR);
            }

            $media->Title = $presentation->getTitle();
            $media->Default = $image->default;
            $media->Sort = $image->order;

            try {
                $media->write();
                $presentation->Media()->add($media);
                self::log("[PresentationMedia][Created] {$media->getTitle()}", self::SUCCESS);
            } catch (Exception $e) {
                self::log($e->getMessage(), self::ERROR);
            }
        } else {
            $media->Title = $presentation->getTitle();
            $media->Default = $image->default;
            $media->Sort = $image->order;

            if ($media->isChanged()) {
                try {
                    $media->write();
                    self::log("[PresentationMedia][Updated] {$media->getTitle()}", self::SUCCESS);
                } catch (Exception $e) {
                    self::log($e->getMessage(), self::ERROR);
                }
            }
        }

        if (!$media->isPublished()) {
            $media->publishSingle();
        }

        return $media;
    }

    /**
     * Import the Bed
     *
     * @param PresentationSpecificationsBed $bed
     * @param Presentation $presentation
     */
    private static function importBed($bed, &$presentation)
    {
        try {
            $bed = PresentationBed::findOrMake($bed);
            $presentation->Beds()->add($bed);
            self::log("[PresentationBed][{$bed->ID}] {$bed->getTitle()}", self::SUCCESS);
        } catch (Exception $e) {
            self::log($e->getMessage(), self::ERROR);
        }
    }

    /**
     * Import the Division
     *
     * @param PresentationSpecificationsSpecsDivision $division
     * @param Presentation $presentation
     */
    private static function importDivision($division, &$presentation)
    {
        try {
            $division = PresentationDivision::findOrMake($division);
            $presentation->Divisions()->add($division);
            self::log("[PresentationDivision][{$division->ID}] {$division->getTitle()}", self::SUCCESS);
        } catch (Exception $e) {
            self::log($e->getMessage(), self::ERROR);
        }
    }

    /**
     * Import the accessories
     *
     * @param PresentationSpecificationsAccessory $accessory
     * @param Presentation $presentation
     */
    private static function importAccessories($accessory, &$presentation)
    {
        $filter = [
            'Category' => $accessory->category,
            'Description' => $accessory->description
        ];

        try {
            if (!$accessoryObj = $presentation->Accessories()->filter($filter)->first()) {
                $accessoryObj = PresentationAccessory::create($filter);
                $presentation->Accessories()->add($accessoryObj);
                self::log("[PresentationAccessory][{$accessoryObj->ID}] {$accessoryObj->getTitle()}", self::SUCCESS);
            }

            if (is_array($accessory->sub) && !empty($accessory->sub)) {
                foreach ($accessory->sub as $sub) {
                    try {
                        $sub = PresentationAccessorySub::findOrMake($sub);
                        $accessoryObj->Sub()->add($sub);
                        self::log("[PresentationAccessorySub][{$sub->ID}] {$sub->getTitle()}", self::SUCCESS);
                    } catch (Exception $e) {
                        self::log($e->getMessage(), self::ERROR);
                    }
                }
            }

        } catch (Exception $e) {
            self::log($e->getMessage(), self::ERROR);
        }
    }

    /**
     * Log messages to the console or cron log
     *
     * @param $message
     * @param $code
     */
    protected static function log($message, $code)
    {
        switch ($code) {
            case self::ERROR:
                echo "[ ERROR ] {$message}\n";
                break;
            case self::WARN:
                echo "[WARNING] {$message}\n";
                break;
            case self::SUCCESS:
                echo "[SUCCESS] {$message}\n";
                break;
            case self::NOTICE:
            default:
                echo "[NOTICE ] {$message}\n";
                break;
        }
    }
}
