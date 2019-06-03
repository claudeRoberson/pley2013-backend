<?php

namespace operations\v1\Warehouse\Stock;

/** @copyright Pley (c) 2017, All Rights Reserved */

/**
 * The <kbd>StockGridController</kbd> responsible for querying data and performing
 * filtering operations on item parts grid view
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */

class StockGridController extends \operations\v1\BaseAuthController
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

    // GET /warehouse/stock/index
    public function index()
    {
        \RequestHelper::checkGetRequest();
        $parts = $this->stockManager->getAllStocksCollection();
        $response = [];
        return \Response::json($response);
    }

    // GET /warehouse/stock/search/{term}
    public function search($term)
    {
        \RequestHelper::checkGetRequest();
        $parts = $this->stockManager->findPartByTerm($term);
        $response = [];
        return \Response::json($response);
    }
}