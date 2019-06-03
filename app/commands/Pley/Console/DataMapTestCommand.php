<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Console;

use \Illuminate\Console\Command;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;
use \Symfony\Component\Console\Input\InputOption;

use \Pley\DataMap\Annotations\Meta;

/**
 * The <kbd>DataMapTestCommand</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 */
class DataMapTestCommand extends Command
{
    use \Pley\Console\ConsoleOutputTrait;
    
     /**
     * The console command name.
     * @var string
     */
    protected $name = 'pleyTB:DataMapTest';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Test';
    
    /** @var \Pley\Db\AbstractDatabaseManager */
    protected $_dbManager;
    
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        
        $this->_dbManager = \App::make('\Pley\Db\AbstractDatabaseManager');

        $this->_setLogOutput(true);
    }
    
    protected function getOptions()
    {
        return [
            [
                'runs',
                null,
                InputOption::VALUE_REQUIRED, 
                'Number of runs to pseudo-emulate stress.'
            ]
        ];
    }
    
    public function fire()
    {
        $pstmt = $this->_dbManager->prepare('SELECT * FROM `profile_subscription_shipment`');
        $pstmt->execute();
        $resultSet = $pstmt->fetchAll(\PDO::FETCH_ASSOC);
        $rowCount  = $pstmt->rowCount();
        $pstmt->closeCursor();
        
        $totalRuns = $this->input->getOption('runs');
        echo "Processing {$rowCount} rows for {$totalRuns} cycle(s)\n";
        
        $daoToEntity = new DaoToEntity();
        echo "Processing with DAO\n";
        $timeStart = microtime(true);
        for ($run = 0; $run < $totalRuns; $run ++) {
            for ($i = 0; $i < $rowCount; $i++) {
                $daoToEntity->toEntity($resultSet[$i]);
            }
        }
        $timeEnd = microtime(true);
        $daoTotalTime = $timeEnd - $timeStart;
        echo 'DAO  time : ', $this->formatMicrotime($daoTotalTime), "\n";
        
        echo "Processing with Hydrator\n";
        $timeStart = microtime(true);
        for ($run = 0; $run < $totalRuns; $run ++) {
            for ($i = 0; $i < $rowCount; $i++) {
                $pssEntity = new PSSEntity();
                $pssEntity->mapFromRow($resultSet[$i]);
            }
        }
        $timeEnd = microtime(true);
        $hidratorTotalTime = $timeEnd - $timeStart;
        
        echo 'EMap time : ', $this->formatMicrotime($hidratorTotalTime), "\n";
    }
    
    public function formatMicrotime($microtime)
    {
        $secs = (int)$microtime;
        $usecs = $microtime - $secs;
        
        return date('H:i:s', $secs) . substr($usecs, 1);
    }
}

class DaoToEntity extends \Pley\Dao\Profile\ProfileSubscriptionShipmentDao
{
    public function toEntity($dbRecord) {
        return $this->_toEntity($dbRecord);
    }
}

/**
 * @Meta\Table(name="profile_subscription_shipment")
 */
class PSSEntity extends \Pley\DataMap\Entity
{
    /**
     * @var int
     * @Meta\Property(fillable=false, column="id")
     */
    protected $_id;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="user_id")
     */
    protected $_userId;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="user_profile_id")
     */
    protected $_profileId;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="profile_subscription_id")
     */
    protected $_profileSubscriptionId;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="type_shipment_source_id")
     */
    protected $_shipmentSourceType;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="shipment_source_id")
     */
    protected $_shipmentSourceId;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="subscription_schedule_id")
     */
    protected $_subscriptionScheduleId;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="status")
     */
    protected $_status;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="type_shirt_size_id")
     */
    protected $_shirtSize;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="carrier_id")
     */
    protected $_carrierId;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="carrier_service_id")
     */
    protected $_carrierServiceId;
    /**
     * @var float
     * @Meta\Property(fillable=true, column="carrier_rate")
     */
    protected $_carrierRate;
    /**
     * @var string
     * @Meta\Property(fillable=true, column="label_url")
     */
    protected $_labelUrl;
    /**
     * @var string
     * @Meta\Property(fillable=true, column="profile_subscripttracking_noion_id")
     */
    protected $_trackingNo;
    /**
     * @var string
     * @Meta\Property(fillable=true, column="v_ship_id")
     */
    protected $_vendorShipId;
    /**
     * @var string
     * @Meta\Property(fillable=true, column="v_ship_tracker_id")
     */
    protected $_vendorShipTrackerId;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="shipped_at")
     */
    protected $_shippedAt;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="delivered_at")
     */
    protected $_deliveredAt;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="label_purchase_at")
     */
    protected $_labelPurchaseAt;
    /**
     * @var string
     * @Meta\Property(fillable=true, column="street_1")
     */
    protected $_street1;
    /**
     * @var string
     * @Meta\Property(fillable=true, column="street_2")
     */
    protected $_street2;
    /**
     * @var string
     * @Meta\Property(fillable=true, column="city")
     */
    protected $_city;
    /**
     * @var string
     * @Meta\Property(fillable=true, column="state")
     */
    protected $_state;
    /**
     * @var string
     * @Meta\Property(fillable=true, column="zip")
     */
    protected $_zip;
    /**
     * @var string
     * @Meta\Property(fillable=true, column="country")
     */
    protected $_country;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="shipping_zone")
     */
    protected $_zone;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="label_lease")
     */
    protected $_labelLease;
    
    public function getId()
    {
        return $this->_id;
    }

    public function getUserId()
    {
        return $this->_userId;
    }

    public function getProfileId()
    {
        return $this->_profileId;
    }

    public function getProfileSubscriptionId()
    {
        return $this->_profileSubscriptionId;
    }

    public function getShipmentSourceType()
    {
        return $this->_shipmentSourceType;
    }

    public function getShipmentSourceId()
    {
        return $this->_shipmentSourceId;
    }

    public function getSubscriptionScheduleId()
    {
        return $this->_subscriptionScheduleId;
    }

    public function getStatus()
    {
        return $this->_status;
    }

    public function getShirtSize()
    {
        return $this->_shirtSize;
    }

    public function getCarrierId()
    {
        return $this->_carrierId;
    }

    public function getCarrierServiceId()
    {
        return $this->_carrierServiceId;
    }

    public function getCarrierRate()
    {
        return $this->_carrierRate;
    }

    public function getLabelUrl()
    {
        return $this->_labelUrl;
    }

    public function getTrackingNo()
    {
        return $this->_trackingNo;
    }

    public function getVendorShipId()
    {
        return $this->_vendorShipId;
    }

    public function getVendorShipTrackerId()
    {
        return $this->_vendorShipTrackerId;
    }

    public function getShippedAt()
    {
        return $this->_shippedAt;
    }

    public function getDeliveredAt()
    {
        return $this->_deliveredAt;
    }

    public function getLabelPurchaseAt()
    {
        return $this->_labelPurchaseAt;
    }

    public function getStreet1()
    {
        return $this->_street1;
    }

    public function getStreet2()
    {
        return $this->_street2;
    }

    public function getCity()
    {
        return $this->_city;
    }

    public function getState()
    {
        return $this->_state;
    }

    public function getZip()
    {
        return $this->_zip;
    }

    public function getCountry()
    {
        return $this->_country;
    }

    public function getZone()
    {
        return $this->_zone;
    }

    public function getLabelLease()
    {
        return $this->_labelLease;
    }

    public function setId($id)
    {
        $this->_id = $id;
    }

    public function setUserId($userId)
    {
        $this->_userId = $userId;
    }

    public function setProfileId($profileId)
    {
        $this->_profileId = $profileId;
    }

    public function setProfileSubscriptionId($profileSubscriptionId)
    {
        $this->_profileSubscriptionId = $profileSubscriptionId;
    }

    public function setShipmentSourceType($shipmentSourceType)
    {
        $this->_shipmentSourceType = $shipmentSourceType;
    }

    public function setShipmentSourceId($shipmentSourceId)
    {
        $this->_shipmentSourceId = $shipmentSourceId;
    }

    public function setSubscriptionScheduleId($subscriptionScheduleId)
    {
        $this->_subscriptionScheduleId = $subscriptionScheduleId;
    }

    public function setStatus($status)
    {
        $this->_status = $status;
    }

    public function setShirtSize($shirtSize)
    {
        $this->_shirtSize = $shirtSize;
    }

    public function setCarrierId($carrierId)
    {
        $this->_carrierId = $carrierId;
    }

    public function setCarrierServiceId($carrierServiceId)
    {
        $this->_carrierServiceId = $carrierServiceId;
    }

    public function setCarrierRate($carrierRate)
    {
        $this->_carrierRate = $carrierRate;
    }

    public function setLabelUrl($labelUrl)
    {
        $this->_labelUrl = $labelUrl;
    }

    public function setTrackingNo($trackingNo)
    {
        $this->_trackingNo = $trackingNo;
    }

    public function setVendorShipId($vendorShipId)
    {
        $this->_vendorShipId = $vendorShipId;
    }

    public function setVendorShipTrackerId($vendorShipTrackerId)
    {
        $this->_vendorShipTrackerId = $vendorShipTrackerId;
    }

    public function setShippedAt($shippedAt)
    {
        $this->_shippedAt = $shippedAt;
    }

    public function setDeliveredAt($deliveredAt)
    {
        $this->_deliveredAt = $deliveredAt;
    }

    public function setLabelPurchaseAt($labelPurchaseAt)
    {
        $this->_labelPurchaseAt = $labelPurchaseAt;
    }

    public function setStreet1($street1)
    {
        $this->_street1 = $street1;
    }

    public function setStreet2($street2)
    {
        $this->_street2 = $street2;
    }

    public function setCity($city)
    {
        $this->_city = $city;
    }

    public function setState($state)
    {
        $this->_state = $state;
    }

    public function setZip($zip)
    {
        $this->_zip = $zip;
    }

    public function setCountry($country)
    {
        $this->_country = $country;
    }

    public function setZone($zone)
    {
        $this->_zone = $zone;
    }

    public function setLabelLease($labelLease)
    {
        $this->_labelLease = $labelLease;
    }


}
