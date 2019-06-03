<?php

namespace Pley\Console\Waitlist;

/** @copyright Pley (c) 2017, All Rights Reserved */

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The <kbd>RefreshCurrencyRatesCommand</kbd>
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class SendOutPaymentReminders extends Command
{

    use \Pley\Console\ConsoleOutputTrait;

    /**
     * The console command name.
     * @var string
     */
    protected $name = 'pleyTB:waitlist:notifyFailedWaitlistPayments';
    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Cronjob to waitlist customers, which payment has been failed.';
    /** @var \Pley\Config\ConfigInterface */
    protected $_config;
    /** @var \Pley\Repository\User\UserWaitlistRepository */
    protected $_userWaitlistRepo;
    /** @var \Pley\Db\AbstractDatabaseManager */
    protected $_dbManager;
    /** @var \Pley\Mail\AbstractMail */
    protected $_mail;

    protected $_notifyAfterDaysPassed = [
        3,
        7,
        14,
        21,
    ];

    /**
     * @var \Pley\Price\PriceManager
     */
    protected $_priceManager;

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->_config = \App::make('\Pley\Config\ConfigInterface');
        $this->_dbManager = \App::make('\Pley\Db\AbstractDatabaseManager');
        $this->_mail = \App::make('\Pley\Mail\AbstractMail');

        $this->_userWaitlistRepo = \App::make('\Pley\Repository\User\UserWaitlistRepository');

        $this->_setLogOutput(true);
    }

    public function fire()
    {
        $this->info('Sending out emails...');
        foreach ($this->_notifyAfterDaysPassed as $daysNum) {
            $waitlistItems = $this->_userWaitlistRepo->findWaitlistFailedByDaysAgo($daysNum);
            $this->info(count($waitlistItems) . ' notfications for waitlists attempted ' . $daysNum . ' days ago');
            foreach ($waitlistItems as $waitlist) {
                \Event::fire(\Pley\Enum\EventEnum::WAITLIST_PAYMENT_FAILED, ['userWaitlist' => $waitlist]);
            }
        }
        $this->info('Email sendout complete...');
    }
}