<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace operations\v1\Warehouse\Stock;

/**
 * The <kbd>StockController</kbd> responsible on making stock inductions and
 * getting all the stock related information for front-end.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class StockController extends \operations\v1\BaseAuthController
{
    /** @var \Pley\Operations\StockManager */
    protected $stockManager;

    public function __construct(
        \Pley\Operations\StockManager $stockManager
    )
    {
        parent::__construct();
        $this->stockManager = $stockManager;
    }

    // GET /warehouse/subscription
    public function getSubscriptions()
    {
        $response = [];
        $subscriptionsList = $this->stockManager->getSubscriptionsList();
        foreach ($subscriptionsList as $subscription) {
            $response[] = $subscription->toArray();
        }
        return \Response::json($response);
    }

    // GET /warehouse/box/{boxId}/parts
    public function getBoxParts($boxId)
    {
        $response = [
            'parts' => [],
            'inductions' => []
        ];
        $boxPartsList = $this->stockManager->getBoxPartsList($boxId);
        $boxInductions = $this->stockManager->getBoxInductionsList($boxId);

        foreach ($boxPartsList as $part) {
            $response['parts'][] = $part->toArray();
        }
        foreach ($boxInductions as $induction) {
            $response['inductions'][] = $induction->toArray();
        }
        return \Response::json($response);
    }

    // POST /warehouse/stock/induction
    public function createPartStockInduction()
    {
        \RequestHelper::checkPostRequest();
        \RequestHelper::checkJsonRequest();

        $inductionData = \Input::json('induction');
        $induction = $this->stockManager->createStockInduction($inductionData);
        return \Response::json($induction);
    }
}