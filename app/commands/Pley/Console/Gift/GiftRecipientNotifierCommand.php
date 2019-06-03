<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Console\Gift;

use \Illuminate\Console\Command;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;

/** ♰
 * The <kbd>GiftRecipientNotifierCommand</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 */
class GiftRecipientNotifierCommand extends Command
{
    use \Pley\Console\ConsoleOutputTrait;
    
    /**
     * The console command name.
     * @var string
     */
    protected $name = 'pleyTB:giftRecipientNotifier';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Cronjob to notify Gift recipients.';
    
    /** @var \Pley\Config\ConfigInterface */
    protected $_config;
    /** @var \Pley\Db\AbstractDatabaseManager */
    protected $_dbManager;
    /** @var \Pley\Mail\AbstractMail */
    protected $_mail;
    /** @var \Pley\Dao\Gift\GiftDao */
    protected $_giftDao;
    /** @var \Pley\Dao\Gift\GiftPriceDao */
    protected $_giftPriceDao;
    /** @var \Pley\Dao\Payment\PaymentPlanDao */
    protected $_pymtPlanDao;
    /** @var \Pley\Dao\Subscription\SubscriptionDao */
    protected $_subscriptionDao;
    
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        
        $this->_config          = \App::make('\Pley\Config\ConfigInterface');
        $this->_dbManager       = \App::make('\Pley\Db\AbstractDatabaseManager');
        $this->_mail            = \App::make('\Pley\Mail\AbstractMail');
        $this->_giftDao         = \App::make('\Pley\Dao\Gift\GiftDao');
        $this->_giftPriceDao    = \App::make('\Pley\Dao\Gift\GiftPriceDao');
        $this->_pymtPlanDao     = \App::make('\Pley\Dao\Payment\PaymentPlanDao');
        $this->_subscriptionDao = \App::make('\Pley\Dao\Subscription\SubscriptionDao');

        $this->_setLogOutput(true);
    }
    
    public function fire()
    {
        $startTime = microtime(true);

        $giftList = $this->_getEligibleGiftList();
        
        $this->line(sprintf('Processing %d emails for %s.', count($giftList), date('Y-m-d')));
        
        foreach ($giftList as $gift) {
            $giftPrice    = $this->_getGiftPrice($gift->getGiftPriceId());
            $subscription = $this->_getSubscription($gift->getSubscriptionId());
            
            $this->_sendEmail($gift, $giftPrice, $subscription);
            
            $gift->setIsEmailSent();
            $this->_giftDao->save($gift);
        }
        
        $endTime     = microtime(true);
        $elapsedTime = $endTime - $startTime;
        $this->line(sprintf('Elapsed Time: %.3f secs', $elapsedTime));
    }
    
    /** ♰
     * @return \Pley\Entity\Gift\Gift[]
     */
    private function _getEligibleGiftList()
    {
        $sql = 'SELECT `id` FROM `gift` '
             . 'WHERE `is_redeemed` = 0 '
             .   'AND `is_email_sent` = 0 '
             .   'AND `notify_date` <= DATE(NOW())';
        
        $prepStmt = $this->_dbManager->prepare($sql);
        $prepStmt->execute();
        
        $resultSet = $prepStmt->fetchAll(\PDO::FETCH_ASSOC);
        $rowCount  = $prepStmt->rowCount();
        
        $prepStmt->closeCursor();
        
        for ($i = 0; $i < $rowCount; $i++) {
            $resultSet[$i] = $this->_giftDao->find($resultSet[$i]['id']);
        }
        
        return $resultSet;
    }
    
    private function _sendEmail(
            \Pley\Entity\Gift\Gift $gift, 
            \Pley\Entity\Gift\GiftPrice $giftPrice,
            \Pley\Entity\Subscription\Subscription $subscription)
    {
        $mailTagCollection = new \Pley\Mail\MailTagCollection($this->_config);
        $mailTagCollection->addEntity($gift);
        $mailTagCollection->addEntity($giftPrice);
        $mailTagCollection->addEntity($subscription);
        $mailTagCollection->addEntity($giftPrice->getEquivalentPaymentPlan());
        $mailTemplateId = \Pley\Enum\Mail\MailTemplateEnum::GIFT_RECIPIENT;
        
        $displayName = $gift->getToFirstName() . ' ' . $gift->getToLastName();
        $mailUserTo = new \Pley\Mail\MailUser($gift->getToEmail(), $displayName);

        $mailOptions = ['subjectName' => $subscription->getName()];
        $this->_mail->send($mailTemplateId, $mailTagCollection, $mailUserTo, $mailOptions);
    }
    
    /** ♰
     * @param int $giftPriceId
     * @return \Pley\Entity\Gift\GiftPrice
     */
    private function _getGiftPrice($giftPriceId)
    {
        $objCacheKey = __FUNCTION__;
        
        if (!isset($this->_objCache[$objCacheKey][$giftPriceId])) {
            $giftPrice = $this->_giftPriceDao->find($giftPriceId);
            $giftPrice->setEquivalentPaymentPlan(
                $this->_pymtPlanDao->find($giftPrice->getEquivalentPaymentPlanId())
            );
            $this->_objCache[$objCacheKey][$giftPriceId] = $giftPrice;
        }
        
        return $this->_objCache[$objCacheKey][$giftPriceId];
    }
    
    /** ♰
     * @param int $subscriptionId
     * @return \Pley\Entity\Subscription\Subscription
     */
    private function _getSubscription($subscriptionId)
    {
        $objCacheKey = __FUNCTION__;
        
        if (!isset($this->_objCache[$objCacheKey][$subscriptionId])) {
            $subscription = $this->_subscriptionDao->find($subscriptionId);
            $this->_objCache[$objCacheKey][$subscriptionId] = $subscription;
        }
        
        return $this->_objCache[$objCacheKey][$subscriptionId];
    }
}
