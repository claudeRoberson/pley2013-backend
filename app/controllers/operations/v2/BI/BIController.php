<?php

namespace operations\v2\BI;

use Pley\Stats\Stats;

    class BIController extends \BaseController
    {

        public function __construct()
        {
        }

        public function handle()
        {
            \RequestHelper::checkGetRequest();

            $stats=new Stats();
            $churnCohort=$stats->calc();
            $response=$churnCohort;
            return \Response::json($response);
        }

    }

