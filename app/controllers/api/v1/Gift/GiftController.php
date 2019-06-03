<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace api\v1\Gift;

use \Pley\Config\ConfigInterface as Config;
use \Pley\Mail\AbstractMail as Mail;

/**
 * The <kbd>GiftController</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 */
class GiftController extends \api\v1\BaseController
{
    /** @var \Pley\Config\ConfigInterface */
    protected $_config;
    /** @var \Pley\Mail\AbstractMail */
    protected $_mail;
    /** @var \Pley\Subscription\SubscriptionManager */
    protected $_subscriptionMgr;
    /** @var \Pley\Dao\Payment\PaymentPlanDao */
    protected $_paymentPlanDao;
    /** @var \Pley\Dao\Gift\GiftDao */
    protected $_giftDao;
    /** @var \Pley\Dao\Gift\GiftPriceDao */
    protected $_giftPriceDao;
    /** @var \Pley\User\UserBillingManager */
    protected $_userBillingMgr;
    
    public function __construct(
            Config $config, Mail $mail,
            \Pley\Subscription\SubscriptionManager $subscriptionMgr, 
            \Pley\Dao\Payment\PaymentPlanDao $paymentPlanDao,
            \Pley\Dao\Gift\GiftDao $giftDao,
            \Pley\Dao\Gift\GiftPriceDao $giftPriceDao,
            \Pley\User\UserBillingManager $userBillingMgr)
    {
        $this->_config          = $config;
        $this->_mail            = $mail;
        $this->_subscriptionMgr = $subscriptionMgr;
        $this->_paymentPlanDao  = $paymentPlanDao;
        $this->_giftDao         = $giftDao;
        $this->_giftPriceDao    = $giftPriceDao;
        $this->_userBillingMgr  = $userBillingMgr;
    }
    
    // GET /gift/subscription/{id}
    public function infoForGift($subscriptionId)
    {
        \RequestHelper::checkGetRequest();
        
        $subscription = $this->_subscriptionMgr->getSubscription($subscriptionId);
        \ValidationHelper::entityExist($subscription, \Pley\Entity\Subscription\Subscription::class);
        
        $responseStructure = [
            'subscription'    => [
                'name'        => $subscription->getName(),
                'description' => $subscription->getDescription(),
                'period'      => $subscription->getPeriod(),
                'periodUnit'  => $subscription->getPeriodUnit(),
            ],
            'giftPriceList' => [],
        ];
        
        $giftPriceList = $this->_getGiftPriceList($subscription->getGiftPriceIdList());
        /* @var $giftPrice \Pley\Entity\Gift\GiftPrice */
        foreach ($giftPriceList as $giftPrice) {
            $responseStructure['giftPriceList'][] = [
                'id'          => $giftPrice->getId(),
                'title'       => $giftPrice->getTitle(),
                'period'      => $giftPrice->getEquivalentPaymentPlan()->getPeriod(),
                'periodUnit'  => $giftPrice->getEquivalentPaymentPlan()->getPeriodUnit(),
                'price'       => [
                    'total' => $giftPrice->getPriceTotal(),
                    'unit'  => $giftPrice->getPriceUnit(),
                ]
            ];
        }
        
        return \Response::json($responseStructure);
    }
    
    // POST /gift/add
    public function addGift()
    {
        \RequestHelper::checkPostRequest();
        \RequestHelper::checkJsonRequest();
        
        $json = \Input::json()->all();
        
        $giftPrice    = $this->_getGiftPrice($json['gift']['giftPriceId']);
        $subscription = $this->_subscriptionMgr->getSubscription($json['gift']['subscriptionId']);
        
        \ValidationHelper::entityExist($giftPrice, \Pley\Entity\Gift\GiftPrice::class);
        \ValidationHelper::entityExist($subscription, \Pley\Entity\Subscription\Subscription::class);
        
        $creditCard = $this->_validateGiftPurchase($json);
        
        $gift = \Pley\Entity\Gift\Gift::withNew(
            $subscription->getId(),
            $giftPrice->getId(), 
            $json['senderInfo']['firstName'],
            $json['senderInfo']['lastName'],
            $json['senderInfo']['email'],
            $json['recipientInfo']['firstName'],
            $json['recipientInfo']['lastName'],
            $json['recipientInfo']['email'],
            $json['gift']['message'], 
            \Pley\Util\DateTime::strToTime($json['gift']['notifyByDate'])
        );
        
        // Paying for the gift and creating it (this updates the $gift reference with Token and vendor data)
        $this->_userBillingMgr->purchaseGift($gift, $subscription, $giftPrice, $creditCard);
        
        // Now sending the confirmation email to the Sender
        $mailTagCollection = new \Pley\Mail\MailTagCollection($this->_config);
        $mailTagCollection->addEntity($gift);
        $mailTagCollection->addEntity($giftPrice);
        $mailTagCollection->addEntity($subscription);
        $mailTagCollection->addEntity($giftPrice->getEquivalentPaymentPlan());
        $mailTemplateId = \Pley\Enum\Mail\MailTemplateEnum::GIFT_SENDER;
        
        $displayName = $gift->getFromFirstName() . ' ' . $gift->getFromLastName();
        $mailUserTo = new \Pley\Mail\MailUser($gift->getFromEmail(), $displayName);

        $this->_mail->send($mailTemplateId, $mailTagCollection, $mailUserTo);
        
        return \Response::json([
            'success' => true,
            'token'   => strtoupper($gift->getToken()),
        ]);
    }
    
    // GET /gift/token/{{token}}
    public function getTokenDetails($token)
    {
        \RequestHelper::checkGetRequest();
        
        $gift = $this->_giftDao->findByToken(strtolower($token));
        
        \ValidationHelper::entityExist($gift, \Pley\Entity\Gift\Gift::class);
        if ($gift->isRedeemed()) {
            throw new \Exception('Gift Redeemed');
        }
        
        $subscription = $this->_subscriptionMgr->getSubscription($gift->getSubscriptionId());
        $giftPrice    = $this->_getGiftPrice($gift->getGiftPriceId());
        
        
        $responseStructure = [
            'subscription'    => [
                'name'        => $subscription->getName(),
                'description' => $subscription->getDescription(),
                'period'      => $subscription->getPeriod(),
                'periodUnit'  => $subscription->getPeriodUnit(),
            ],
            'gift' => [
                'id'          => $giftPrice->getId(),
                'title'       => $giftPrice->getTitle(),
                'period'      => $giftPrice->getEquivalentPaymentPlan()->getPeriod(),
                'periodUnit'  => $giftPrice->getEquivalentPaymentPlan()->getPeriodUnit(),
                'price'       => [
                    'total' => $giftPrice->getPriceTotal(),
                    'unit'  => $giftPrice->getPriceUnit(),
                ]
            ],
            'sender' => [
                'firstName' => $gift->getFromFirstName(),
                'lastName'  => $gift->getFromLastName(),
                'email'     => $gift->getFromEmail(),
                'message'   => $gift->getMessage(),
            ]
        ];
        
        return \Response::json($responseStructure);
    }
    
    /** ♰
     * Returns a list of <kbd>GiftPrice</kbd> objects for the supplied ID list.
     * @param int[] $priceIdList
     * @return \Pley\Entity\Gift\GiftPrice[]
     */
    private function _getGiftPriceList($priceIdList)
    {
        $giftPriceList = [];
        foreach ($priceIdList as $giftPriceId) {
            $giftPriceList[] = $this->_getGiftPrice($giftPriceId);
        }
        
        return $giftPriceList;
    }
    
    /** ♰
     * @param int $giftPriceId
     * @return \Pley\Entity\Gift\GiftPrice
     */
    private function _getGiftPrice($giftPriceId)
    {
        $giftPrice = $this->_giftPriceDao->find($giftPriceId);
        $giftPrice->setEquivalentPaymentPlan(
            $this->_paymentPlanDao->find($giftPrice->getEquivalentPaymentPlanId())
        );
        return $giftPrice;
    }
    
    /**
     * Validates the input sent for a gift purchase and returns the validated CreditCard object.
     * @param array $data
     * @return \Pley\Payment\Method\CreditCard
     */
    private function _validateGiftPurchase($data)
    {
        \ValidationHelper::validate($data, [
            'gift'          => 'required|array',
            'billing'       => 'required|array',
            'senderInfo'    => 'required|array',
            'recipientInfo' => 'required|array',
        ]);
        \ValidationHelper::validate($data['gift'], [
            'giftPriceId'    => 'required|integer',
            'subscriptionId' => 'required|integer',
            'message'        => 'sometimes|string',
            'notifyByDate'   => 'required|string',
        ]);
        \ValidationHelper::validate($data['senderInfo'], [
            'firstName' => 'required|string',
            'lastName'  => 'required|string',
            'email'     => 'required|string',
        ]);
        \ValidationHelper::validate($data['recipientInfo'], [
            'firstName' => 'required|string',
            'lastName'  => 'required|string',
            'email'     => 'required|string',
        ]);
        \ValidationHelper::validate($data['billing'], [
            'ccNumber'       => 'required|numeric',
            'cvv'            => 'required|numeric',
            'expMonth'       => 'required|numeric',
            'expYear'        => 'required|numeric',
            'billingAddress' => 'required'
        ]);
        \ValidationHelper::validate($data['billing']['billingAddress'], [
            'street1'  => 'required',
            'street2'  => 'sometimes',
            'city'     => 'required|alpha_space',
            'state'    => 'required|alpha',
            'country'  => 'required|alpha_space|in:US',
            'zip'      => 'required'
        ]);
        
        $creditCard = new \Pley\Payment\Method\CreditCard(
            $data['billing']['ccNumber'], $data['billing']['expMonth'], $data['billing']['expYear']
        );
        $creditCard->setCVV($data['billing']['cvv']);
        $creditCard->setBillingAddress(new \Pley\Payment\Method\BillingAddress(
            $data['billing']['billingAddress']['street1'], 
            $data['billing']['billingAddress']['street2'],
            $data['billing']['billingAddress']['city'], 
            $data['billing']['billingAddress']['state'],
            $data['billing']['billingAddress']['zip'], 
            $data['billing']['billingAddress']['country']
        ));
        
        // Now validating with the Payment Vendor that the card is good to go.
        // Validation requires a User in case of an exception, but since Gifts are handled different,
        // meaning, no user is created, we need to create a fake User object to satisfy the condition
        $giftUser = \Pley\Entity\User\User::dummy();
        $this->_userBillingMgr->valdiateCard($giftUser, $creditCard);
        
        return $creditCard;
    }
}
