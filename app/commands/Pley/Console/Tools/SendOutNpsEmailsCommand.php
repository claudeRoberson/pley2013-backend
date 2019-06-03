<?php

namespace Pley\Console\Tools;

/** @copyright Pley (c) 2017, All Rights Reserved */

use Illuminate\Console\Command;
use Pley\Enum\Shipping\ShipmentStatusEnum;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The <kbd>GrantCreditToSubscribersCommand</kbd>
 *asd
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class SendOutNpsEmailsCommand extends Command
{

    use \Pley\Console\ConsoleOutputTrait;

    /**
     * Amount of surveys to send out per one command run
     */
    const SURVEYS_AMOUNT_PER_RUN = 100;

    const DELIVERED_FROM_DATE = '2017-06-01';
    /**
     * The console command name.
     * @var string
     */
    protected $name = 'pleyTB:Tools:sendOutNpsEmails';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Sends out NPS emails to users by a given month';

    /** @var \Pley\Config\ConfigInterface */
    protected $_config;
    /** @var \Pley\Db\AbstractDatabaseManager */
    protected $_dbManager;
    /**
     * @var \Pley\Payment\PaymentManagerFactory
     */
    protected $_paymentManagerFactory;
    /**
     * @var \Pley\Repository\User\UserRepository
     */
    protected $_userRepository;

    /**
     * @var \Pley\Nps\NpsManagerInterface
     */
    protected $_npsManager;

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->_config = \App::make('\Pley\Config\ConfigInterface');
        $this->_dbManager = \App::make('\Pley\Db\AbstractDatabaseManager');

        $this->_userRepository = \App::make('\Pley\Repository\User\UserRepository');
        $this->_paymentManagerFactory = \App::make('\Pley\Payment\PaymentManagerFactory');
        $this->_npsManager = \App::make('\Pley\Nps\NpsManagerInterface');

        $this->_setLogOutput(true);
    }

    public function fire()
    {
        $this->info('Begin...');

        $users = $this->_getUsersForSurvey();
        $count = count($users);

        if (!$this->confirm(sprintf('We will take 100 users and send a Delighted survey each. Continue? [yes|no]', $count))) {
            $this->error('Operation has been aborted.');
            return;
        }

        foreach ($users as $user) {
            $this->line(sprintf('Processing user ID: [%d]...', $user->getId()));
            $this->_npsManager->addUserToSchedule($user, true, 120); //start sendouts in 120 secs
            $this->line(sprintf('%d users left', --$count));
        }
        $this->info('COMPLETED SUCCESSFULLY!');
    }

    /**
     * @return \Pley\Entity\User\User[]
     */
    protected function _getUsersForSurvey()
    {
        $users = [];

        $sql = '
        SELECT 
  us.user_id,
  us.delivered_at,
  u.survey_scheduled_at
FROM profile_subscription_shipment us
  LEFT JOIN `user_nps` u ON us.user_id = u.user_id
WHERE us.`status` = ? AND us.delivered_at > ? AND u.user_id IS NULL 
ORDER BY us.delivered_at DESC LIMIT ?
        ';
        $pstmt = $this->_dbManager->prepare($sql);
        $pstmt->execute(
            [
                ShipmentStatusEnum::DELIVERED,
                self::DELIVERED_FROM_DATE,
                self::SURVEYS_AMOUNT_PER_RUN
            ]);

        $resultSet = $pstmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($resultSet as $resItem) {
            $users[] = $this->_userRepository->find($resItem['user_id']);
        }
        return $users;
    }
}