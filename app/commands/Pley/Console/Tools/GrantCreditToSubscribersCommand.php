<?php

namespace Pley\Console\Tools;

/** @copyright Pley (c) 2017, All Rights Reserved */

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The <kbd>GrantCreditToSubscribersCommand</kbd>
 *asd
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class GrantCreditToSubscribersCommand extends Command
{

    use \Pley\Console\ConsoleOutputTrait;

    /**
     * Amount of credit to issue
     */
    const CREDIT_AMOUNT = 5.00;

    /**
     * Subscription ID to use in WHERE clause
     */
    const SUBSCRIPTION_ID = 1; //Disney subscription

    /**
     * Box ID to use in WHERE clause
     */
    const ITEM_ID = 2; //Ariel box
    /**
     * The console command name.
     * @var string
     */
    protected $name = 'pleyTB:Tools:grantCreditToDisneySubscribers';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Grants a one time credit to Disney subscribers';

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

        $this->_setLogOutput(true);
    }

    public function fire()
    {
        $this->info('Begin...');

        $users = $this->_getUsersForCreditIssue();
        $count = count($users);

        if (!$this->confirm(sprintf('There are %d users to process and give a %s$ credit to each. Continue? [yes|no]', $count, self::CREDIT_AMOUNT))) {
            $this->error('Operation has been aborted.');
            return;
        }

        foreach ($users as $user) {
            $this->line(sprintf('Processing user ID: [%d]...', $user->getId()));
            $paymentManager = $this->_paymentManagerFactory->getManager($user->getVPaymentSystemId());

            $paymentManager->addCredit(
                $user,
                self::CREDIT_AMOUNT,
                'Bonus credit for shipping delay.');
            $this->info(sprintf('User ID: [%d] has been granted with credit.', $user->getId()));
            $this->line(sprintf('%d users left', --$count));
        }
        $this->info('CREDIT ISSUE COMPLETED SUCCESSFULLY!');
    }

    /**
     * @return \Pley\Entity\User\User[]
     */
    protected function _getUsersForCreditIssue()
    {
        /**
         * get users, which are valid for issuing credit.
         * For this particular case - it's the users, who received Ariel box
         */
        $users = [];
        $sql = 'SELECT DISTINCT `ps`.`user_id`
FROM profile_subscription `ps`
  LEFT JOIN profile_subscription_shipment `pss`
    ON `ps`.`id` = `pss`.`profile_subscription_id`
WHERE `ps`.status = 1
      AND `pss`.subscription_id = ?
      AND `pss`.item_id = ?';

        $pstmt = $this->_dbManager->prepare($sql);
        $pstmt->execute([self::SUBSCRIPTION_ID,
            self::ITEM_ID]);

        $resultSet = $pstmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($resultSet as $resItem) {
            $users[] = $this->_userRepository->find($resItem['user_id']);
        }
        return $users;
    }
}