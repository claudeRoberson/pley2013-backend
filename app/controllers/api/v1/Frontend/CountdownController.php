<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace api\v1\Frontend;

use \Pley\Config\ConfigInterface as Config;
use \Pley\Db\AbstractDatabaseManager as DatabaseManager;
use \Pley\Repository\Banner\RevealBannerRepository;

/**
 * @author Sebastian Maldonado (seba@pley.com)
 * @author Seva Yatsiuk (seva@pley.com)
 * @version 1.0
 */
class CountdownController extends \api\v1\BaseController
{
    /** @var \Pley\Config\ConfigInterface */
    protected $_config;
    /** @var \Pley\Db\AbstractDatabaseManager */
    protected $_dbManager;
    /** @var \Pley\Repository\Banner\RevealBannerRepository */
    protected $_revealBannerRepository;


    public function __construct(Config $config,
                                DatabaseManager $dbManager,
                                RevealBannerRepository $revealBannerRepository)
    {
        $this->_config = $config;
        $this->_dbManager = $dbManager;
        $this->_revealBannerRepository = $revealBannerRepository;
    }

    // POST countdown/get
    public function getCountdown()
    {
        $now = new \DateTime(null, new \DateTimeZone('UTC'));

        $banners = $this->_revealBannerRepository->findActiveByDate($now);
        $line = [];
        $line['enabled'] = false;
        foreach ($banners as $banner) {
            $line['enabled'] = $banner->getEnabled();
            $line['line1_text'] = $banner->getBeforeTimerText();
            $line['line1_link'] = $banner->getBeforeTimerLink();
            $line['timer_target'] = $banner->getTimerTargetDate();
            $line['time_passed_url'] = $banner->getAfterTimerLink();
            $line['time_passed_text'] = $banner->getAfterTimerText();
        }
        $reply = ['status' => true, 'countdown' => $line];

        return \Response::json($reply);
    }
}
