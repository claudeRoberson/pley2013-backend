<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace operations\v1\CustomerService\Gift;

/**
 * The <kbd>GiftController</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package 
 * @subpackage
 */
class GiftController extends \operations\v1\BaseAuthController
{
    /** @var \Pley\Dao\Gift\GiftDao */
    protected $_giftDao;
    /** @var \Pley\Dao\Subscription\SubscriptionDao */
    protected $_subscriptionDao;
    /** @var \Pley\Dao\Gift\GiftPriceDao */
    protected $_giftPriceDao;
    /** @var \Pley\Dao\Payment\PaymentPlanDao */
    protected $_paymentPlanDao;
    
    public function __construct(
            \Pley\Dao\Gift\GiftDao $giftDao,
            \Pley\Dao\Gift\GiftPriceDao $giftPriceDao,
            \Pley\Dao\Subscription\SubscriptionDao $subscriptionDao,
            \Pley\Dao\Payment\PaymentPlanDao $paymentPlanDao)
    {
        parent::__construct();
        
        $this->_giftDao         = $giftDao;
        $this->_giftPriceDao    = $giftPriceDao;
        $this->_subscriptionDao = $subscriptionDao;
        $this->_paymentPlanDao  = $paymentPlanDao;
    }
    
    // POST /cs/user/search
    public function search()
    {
        \RequestHelper::checkPostRequest();
        \RequestHelper::checkJsonRequest();
        
        // Getting the JSON input as an assoc array
        $json = \Input::json()->all();
        
        $rules = ['input'  => 'required|string'];
        \ValidationHelper::validate($json, $rules);
        
        $giftList = $this->_getGiftListForInput($json['input']);
        
        // Helper lists to retrieve the metadata related to the gifts
        $subscriptionMap = [];
        $giftPriceMap    = [];
        
        // Creating the SETs of unique subscription ids and gift price ids
        foreach ($giftList as $gift) {
            if (!isset($subscriptionMap[$gift->getSubscriptionId()])) {
                $subscriptionMap[$gift->getSubscriptionId()] = $this->_subscriptionDao->find($gift->getSubscriptionId());
            }
            
            if (!isset($giftPriceMap[$gift->getGiftPriceId()])) {
                $giftPriceMap[$gift->getGiftPriceId()] = $this->_giftPriceDao->find($gift->getGiftPriceId());
            }
        }
        
        $arrayResponse = [];
        $arrayResponse['giftList']        = $this->_parseMap($giftList, '_parseGift');
        $arrayResponse['subscriptionMap'] = $this->_parseMap($subscriptionMap, '_parseSubscription');
        $arrayResponse['giftPriceMap']    = $this->_parseMap($giftPriceMap, '_parseGiftPrice');

        return \Response::json($arrayResponse);
    }
    
    /**
     * Parses the input and returns a list of <kbd>Gift</kbd> objects that matched the input.
     * @param string $input
     * @return \Pley\Entity\Gift\Gift[]
     */
    private function _getGiftListForInput($input)
    {
        // Replacing any 2 or more spaces in the input string for a single space so that we can
        // split all words correctly for the search
        $sanitizedInput = preg_replace('/ {2,}/', ' ', $input);
        $inputWordList  = explode(' ', $sanitizedInput);
        
        $giftIdList = [];
        foreach ($inputWordList  as $inputWord) {
            $giftIdList = array_merge($giftIdList, $this->_giftDao->csSearchId($inputWord));
        }
        
        $uniqueGiftIdList = array_unique($giftIdList);
        
        $giftList = [];
        foreach ($uniqueGiftIdList as $giftId) {
            $giftList[] = $this->_giftDao->find($giftId);
        }
        
        return $giftList;
    }
    
    private function _parseGift(\Pley\Entity\Gift\Gift $gift)
    {
        $parsedGift = [
            'id'             => $gift->getId(),
            'token'          => strtoupper($gift->getToken()),
            'subscriptionId' => $gift->getSubscriptionId(),
            'giftPriceId'    => $gift->getGiftPriceId(),
            'from'           => [
                'firstName' => $gift->getFromFirstName(),
                'lastName'  => $gift->getFromLastName(),
                'email'     => $gift->getFromEmail(),
            ],
            'to'             => [
                'firstName' => $gift->getToFirstName(),
                'lastName'  => $gift->getToLastName(),
                'email'     => $gift->getToEmail(),
            ],
            'message'        => $gift->getMessage(),
            'notifyDate'     => $gift->getNotifyDate(),
            'vendor'         => [
                'billing' => [
                    'systemId'      => $gift->getVPaymentSystemId(),
                    'transactionId' => $gift->getVPaymentTransactionId(),
                ],
            ],
            'isEmailSent'    => $gift->isEmailSent(),
            'redeemed'       => null,
            'createdAt'      => $gift->getCreatedAt(),
        ];
        
        if (!empty($gift->getRedeemUserId())) {
            $parsedGift['redeemed'] = [
                'userId'     => $gift->getRedeemUserId(),
                'redeemedAt' => $gift->getRedeemedAt(),
            ];
        }
        
        return $parsedGift;
    }
    
    protected function _parseSubscription(\Pley\Entity\Subscription\Subscription $subscription)
    {
        return [
            'id'         => $subscription->getId(),
            'name'       => $subscription->getName(),
            'period'     => $subscription->getPeriod(),
            'periodUnit' => $subscription->getPeriodUnit(),
        ];
    }
    
    protected function _parseGiftPrice(\Pley\Entity\Gift\GiftPrice $giftPrice)
    {
        $paymentPlan = $this->_paymentPlanDao->find($giftPrice->getEquivalentPaymentPlanId());
        
        return [
            'id'         => $giftPrice->getId(),
            'total'      => $giftPrice->getPriceTotal(),
            'period'     => $paymentPlan->getPeriod(),
            'periodUnit' => $paymentPlan->getPeriodUnit(),
        ];
    }
    
    /**
     * Helper method to parse a map of objects into their array representation.
     * <p>This is used to reduce lines of code and make the parse methods more atomic handling only
     * one entity parsing, and this method the loop around that parsing.</p>
     * @param object[] $objectMap
     * @param string   $parseMethod
     * @return array
     */
    private function _parseMap($objectMap, $parseMethod)
    {
        $arrayMap = [];
        foreach ($objectMap as $key => $object) {
            if (isset($user)) {
                $arrayMap[$key] = $this->$parseMethod($object, $user);
            } else {
                $arrayMap[$key] = $this->$parseMethod($object);
            }
        }
        
        return $arrayMap;
    }
}
