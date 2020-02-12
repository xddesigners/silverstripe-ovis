<?php

namespace XD\Ovis\Tasks;

use BuildTask;
use Convert;
use DataObject;
use Folder;
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
    private static $data_mapping = array(
        'id' => 'OvisID',
        'created' => 'OvisCreated',
        'updated' => 'OvisUpdated',
        'userId' => 'UserID',
        'externalAdId' => 'ExternalAdID',
        'guid' => 'GUID',
        'realm' => 'Realm',
        'locale' => 'Locale',
        'status' => 'Status',
        'mediainfo' => array(
            '360' => array(
                'url' => 'Media360Link'
            ),
            'pdf' => 'MediaPDFLink',
            '3D' => 'Media3DLink',
            'taGGleVideo' => 'MediaVideoLink'
        ),
        'banners' => array(
            'aveko' => 'BannerAvekoLink',
            'kampeerkrediet' => 'BannerKampeerkredietLink',
            'finanplaza' => 'BannerFinanplazaLink'
        ),
        'specifications' => array(
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
            'dates' => array(
                'constructionYear' => 'ConstructionYear',
                'constructionMonth' => 'ConstructionMonth',
                'modelYear' => 'ModelYear',
                'dateArrival' => 'DateArrival',
                'datePart1a' => 'DatePart1a',
                'datePurchased' => 'DatePurchased'
            ),
            'mediator' => array(
                'enabled' => 'MediatorEnabled',
                'name' => 'MediatorName',
                'phoneNumber' => 'MediatorPhoneNumber',
                'email' => 'MediatorEmail',
                'description' => 'MediatorDescription'
            ),
            'weightsMeasures' => array(
                'lengthConstruction' => 'LengthConstruction',
                'lengthTotal' => 'LengthTotal',
                'width' => 'Width',
                'height' => 'Height',
                'headroom' => 'Headroom',
                'weightEmpty' => 'WeightEmpty',
                'weightOperational' => 'WeightOperational',
                'weightMaximum' => 'WeightMaximum',
                'capacity' => 'Capacity'
            ),
            'beds' => array(
                'numberOfBeds' => 'NumberOfBeds',
                'numberOfSleepingPlaces' => 'NumberOfSleepingPlaces',
                'bedrooms' => 'Bedrooms',
            ),
            'warranty' => array(
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
            ),
            'prices' => array(
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
            ),
            'pricesRental' => array(
                'price3Hours' => 'RentalPrice3Hours',
                'priceDayPart' => 'RentalPriceDayPart',
                'priceDay' => 'RentalPriceDay',
                'priceWeekend' => 'RentalPriceWeekend',
                'priceWeek' => 'RentalPriceWeek',
                'priceMonth' => 'RentalPriceMonth',
                'deposit' => 'RentalDeposit',
                'noclaim' => 'RentalNoClaim'
            )
        )
    );

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
            DataObject::delete_by_id('XD\Ovis\Models\Presentation', $id);
            self::log("[DELETED] presentation $id", self::NOTICE);
        }

        self::log('Finished: no pages left to query', self::SUCCESS);
        exit(self::SUCCESS);
    }

    public function search($page = 1)
    {
        try {
            $result = Ovis::search(['itemsPerPage' => 100, 'page' => $page]);
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            self::log($e->getMessage(), self::ERROR);
            self::log('Could not parse the OVIS API', self::ERROR);
            exit(self::ERROR);
        } catch (\Exception $e) {
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
                foreach ($images as $image) {
                    self::importMedia($image, $importObj);
                }
            }

            // Beds
            if (($beds = $presentation->specifications->beds->bedSpecifications) && is_array($beds)) {
                foreach ($beds as $bed) {
                    self::importBed($bed, $importObj);
                }
            }

            // Lay-out divisions
            if ( isset($presentation->specifications->specsCaravan) &&
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
        } catch (\Exception $e) {
            self::log($e->getMessage(), self::ERROR);
        }

        return null;
    }

    /**
     * Loop the given data map and possible sub maps
     *
     * @param array $map
     * @param \XD\Ovis\Models\Presentation $object
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
     * @param PresentationMediaImage $image
     * @param Presentation $presentation
     */
    private static function importMedia($image, &$presentation)
    {
        if( self::config()->get('use_clean_images') ) {
            // clean images without labels
            $url = $image->traditional->original->clean->url;
        } else {
            // default images with labels included
            $url = $image->traditional->original->default->url;
        }

        $urlInfo = parse_url($url);
        $urlPath = $urlInfo['path'];
        // Fix: Remove rubbish that OVIS added (/large/normalfitcanvas/blank) after the .jpg filename.
        $exploded = array_filter(explode('/', $urlPath));
        $fileName = array_shift($exploded);
        $folder = Folder::find_or_make("/ovismedia/{$presentation->ID}");
        $path = $folder->getFullPath() . $fileName;
        $relativePath = $folder->getRelativePath() . $fileName;


        // detect labels
        // https://images.ovis.nl/058d8cf733a361df7da2075cc248a291.jpg/large/normalfitcanvas/bovag{orientation:bottomright};label{orientation:topleft,color:FFFFFF,bgcolor:F8A133,text:NIEUW}/



        if (!file_exists($path)) {
            try {
                $response = Ovis::mediaClient()->request('GET', $url, [
                    'sink' => $path
                ]);

                if ($response->getStatusCode() == 404) {
                    unlink($path);
                    self::log("File {$url} was not found", self::ERROR);
                } else {
                    self::log("Downloaded media {$url}", self::SUCCESS);
                }
            } catch (\GuzzleHttp\Exception\GuzzleException $e) {
                self::log($e->getMessage(), self::ERROR);
            }
        }

        /** @var PresentationMedia $media */
        if (!$media = $presentation->Media()->find('Name', $fileName)) {
            $media = PresentationMedia::create();
            $media->setFilename($relativePath);
            $media->setParentID($folder->ID);
            $media->Title = $presentation->getTitle();
            $media->Default = $image->default;
            $media->Sort = $image->order;
            $presentation->Media()->add($media);
            self::log("[PresentationMedia][Created] {$media->getTitle()}", self::SUCCESS);
        } else {
            $media->Title = $presentation->getTitle();
            $media->Default = $image->default;
            $media->Sort = $image->order;
            try {
                $media->write();
                self::log("[PresentationMedia][Updated] {$media->getTitle()}", self::SUCCESS);
            } catch (\Exception $e) {
                self::log($e->getMessage(), self::ERROR);
            }
        }
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
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
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
                    } catch (\Exception $e) {
                        self::log($e->getMessage(), self::ERROR);
                    }
                }
            }

        } catch (\Exception $e) {
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
