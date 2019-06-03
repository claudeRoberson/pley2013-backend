<?php

namespace Pley\Console\Shipment;

use \Illuminate\Console\Command;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;

use \Pley\Enum\Shipping\ShipmentStatusEnum;
use \Pley\Util\DateTime;

/** @copyright Pley (c) 2017, All Rights Reserved */

/**
 * The <kbd>UndeliveredShipmentsCheckCommand</kbd>
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class UndeliveredShipmentsCheckCommand extends Command
{

    use \Pley\Console\ConsoleOutputTrait;

    /**
     * The console command name.
     * @var string
     */
    protected $name = 'pleyTB:shipments:checkUndeliveredShipments';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Cronjob to force set old undelivered shipments as delivered';

    const SHIPPED_DAYS_BEFORE = 20;

    /** @var \Pley\Config\ConfigInterface */
    protected $_config;
    /** @var \Pley\Db\AbstractDatabaseManager */
    protected $_dbManager;
    /** @var \Pley\Dao\Profile\ProfileSubscriptionShipmentDao */
    protected $_profileSubsShipDao;

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->_config = \App::make('\Pley\Config\ConfigInterface');
        $this->_dbManager = \App::make('\Pley\Db\AbstractDatabaseManager');
        $this->_profileSubsShipDao = \App::make('\Pley\Dao\Profile\ProfileSubscriptionShipmentDao');
        $this->_setLogOutput(true);
    }

    public function fire()
    {
        setlocale(LC_MONETARY, 'en_US');
        $this->line('Begin shipments status check ...');

        $data = [
            'subscriptions' => []
        ];

        $shipmentIdsList = $this->_getUndeliveredShipments();

        $this->line('Total of ' . count($shipmentIdsList) . ' shipments found');

        foreach ($shipmentIdsList as $shipmentId){
            $shipment = $this->_profileSubsShipDao->find($shipmentId['id']);
            $shipment->setStatus(ShipmentStatusEnum::DELIVERED);
            $this->_profileSubsShipDao->save($shipment);
        }

        $this->info('Process completed');
    }

    protected function _getUndeliveredShipments(){
        $shippedAtDate = date("Y-m-d", strtotime("-" . self::SHIPPED_DAYS_BEFORE . " days"));

        $this->line('Getting undelivered shipments shipped before ' . $shippedAtDate);

        $sql = '
        SELECT id from profile_subscription_shipment 
        WHERE status IN (?, ?) 
        AND DATE(shipped_at) < ?;
        ';

        $prepStmt = $this->_dbManager->prepare($sql);
        $prepStmt->execute([
            ShipmentStatusEnum::IN_TRANSIT,
            ShipmentStatusEnum::PROCESSED,
            $shippedAtDate
        ]);

        $resultSet = $prepStmt->fetchAll(\PDO::FETCH_ASSOC);
        $prepStmt->closeCursor();
        return $resultSet;
    }
}