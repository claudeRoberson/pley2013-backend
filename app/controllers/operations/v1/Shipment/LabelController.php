<?php /** @copyright Pley (c) 2016, All Rights Reserved */

namespace operations\v1\Shipment;

use \Pley\Db\AbstractDatabaseManager as DatabaseManager;

/** ♰
 * The <kbd>LabelController</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 */
class LabelController extends \operations\v1\BaseAuthController
{
    /** @var \Pley\Db\AbstractDatabaseManager */
    protected $_dbManager;

    protected $_subscriptionManager;

    protected $_shipmentManager;

    protected $_profileSubsShipmentDao;

    public function __construct(
        DatabaseManager $dbManager,
        \Pley\Subscription\SubscriptionManager $subscriptionMgr,
        \Pley\Shipping\AbstractShipmentManager $shipmentManager,
        \Pley\Dao\Profile\ProfileSubscriptionShipmentDao $profileSubsShipmentDao
    ) {
        parent::__construct();

        $this->_dbManager = $dbManager;
        $this->_subscriptionManager = $subscriptionMgr;
        $this->_shipmentManager = $shipmentManager;
        $this->_profileSubsShipmentDao = $profileSubsShipmentDao;
    }

    //PUT /shipment/{intId}/label
    public function purchaseShipmentLabel($profileSubsShipmentId)
    {
        $profileSubsShipment = $this->_profileSubsShipmentDao->find($profileSubsShipmentId);

        if (!$profileSubsShipment->getItemId()) {
            throw new \Exception('Profile subscription shipment Item is not set');
        }
        $shipment = $this->_shipmentManager->createShipment($profileSubsShipment);
        $label = $this->_shipmentManager->purchaseLabel($shipment);
        $profileSubsShipment->setLabel($shipment, $label);
        $this->_profileSubsShipmentDao->save($profileSubsShipment);

        return \Response::json([
            'profileSubscriptionShipmentId' => $profileSubsShipment->getId(),
            'carrierId' => $profileSubsShipment->getCarrierId(),
            'carrierService' => $profileSubsShipment->getCarrierServiceId(),
            'carrierRate' => $profileSubsShipment->getCarrierRate()
        ]);
    }

    //POST /shipment/label/refund
    public function refundShipmentLabels()
    {
        \RequestHelper::checkPostRequest();
        \RequestHelper::checkJsonRequest();

        $json = \Input::json()->all();

        $rules = [
            'shipmentIds'    => 'required',
        ];
        \ValidationHelper::validate($json, $rules);

        $shipmentIds = $json['shipmentIds'];
        $refunded = [];
        foreach ($shipmentIds as $shipmentId) {
            $profileSubscriptionShipment = $this->_profileSubsShipmentDao->find($shipmentId);
            if(!$profileSubscriptionShipment){
                $refunded[$shipmentId] = [
                    'refunded' => false,
                    'reason' => 'No shipment found.'
                ];
                continue;
            }
            if (!$profileSubscriptionShipment->getVendorShipId()) {
                $refunded[$shipmentId] = [
                    'refunded' => false,
                    'reason' => 'No Easypost shipment ID.'
                ];
                continue;
            }
            try {
                /* @var $shipment \EasyPost\Shipment */
                $shipment = \EasyPost\Shipment::retrieve($profileSubscriptionShipment->getVendorShipId());
                $shipment->refund();
                $refunded[$shipmentId] = [
                    'refunded' => true,
                    'reason' => null
                ];
            } catch (\Exception $exception) {
                $refunded[$profileSubscriptionShipment->getId()] = [
                    'refunded' => false,
                    'reason' => $exception->getMessage()
                ];
            }
        }
        return \Response::json($refunded);
    }
}
