<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace api\v1\User;

use Pley\Enum\PaymentSystemEnum;

/**
 * The <kbd>PaymentMethodController</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 */
class UserPaymentMethodController extends \api\v1\BaseAuthController
{
    
    /** @var \Pley\Db\AbstractDatabaseManager */
    protected $_dbManager;
    /** @var \Pley\Dao\User\UserDao */
    protected $_userDao;
    /** @var \Pley\Dao\User\UserAddressDao */
    protected $_userAddressDao;
    /** @var \Pley\Dao\Payment\UserPaymentMethodDao */
    protected $_userPaymentMethodDao;
    /** @var \Pley\Dao\Profile\ProfileSubscriptionDao */
    protected $_profileSubsDao;
    /** @var \Pley\User\UserBillingManager */
    protected $_userBillingMgr;
    
    public function __construct(
            \Pley\Db\AbstractDatabaseManager $dbManager,
            \Pley\Dao\User\UserDao $userDao,
            \Pley\Dao\User\UserAddressDao $userAddressDao,
            \Pley\Dao\Payment\UserPaymentMethodDao $userPaymentMethodDao,
            \Pley\Dao\Profile\ProfileSubscriptionDao $profileSubsDao,
            \Pley\User\UserBillingManager $userBillingManager)
    {
        parent::__construct();
        
        $this->_dbManager = $dbManager;
        
        $this->_userDao              = $userDao;
        $this->_userAddressDao       = $userAddressDao;
        $this->_userPaymentMethodDao = $userPaymentMethodDao;
        $this->_profileSubsDao       = $profileSubsDao;

        $this->_userBillingMgr = $userBillingManager;
    }
    
    // POST /user/payment-method
    public function add()
    {
        \RequestHelper::checkPostRequest();
        \RequestHelper::checkJsonRequest();
        
        $json = \Input::json()->all();
        
        $creditCard = $this->_validateBillingData($json);
        
        $that = $this;
        $paymentMethod = $this->_dbManager->transaction(function() use ($that, $creditCard) {
            return $that->_addClosure($creditCard);
        });
        
        return \Response::json(['id' => $paymentMethod->getId()]);
    }
    
    // PUT /user/payment-method/{$paymentMethodId}
    public function update($paymentMethodId)
    {
        \RequestHelper::checkPutRequest();
        \RequestHelper::checkJsonRequest();
        
        $json = \Input::json()->all();
        
        $billingRules = [
            'expMonth' => 'required',
            'expYear'  => 'required',
        ];
        \ValidationHelper::validate($json, $billingRules);
        
        $paymentMethod = $this->_userPaymentMethodDao->find($paymentMethodId);
        \ValidationHelper::entityExist($paymentMethod, \Pley\Entity\Payment\UserPaymentMethod::class);
        
        // This is just a validation for attack attempts, where a logged in user is trying to update
        // a different user's address by the address ID.
        if ($paymentMethod->getUserId() != $this->_user->getId()) {
            throw new \Exception('Mismatching relationship');
        }
        
        $this->_userBillingMgr->updateCard($this->_user, $paymentMethod, $json['expMonth'], $json['expYear']);
        
        return \Response::json(['id' => $paymentMethod->getId()]);
    }
    
    // PUT /user/payment-method/{$paymentMethodId}/default
    public function setDefault($paymentMethodId)
    {
        \RequestHelper::checkPutRequest();
        
        $paymentMethod = $this->_userPaymentMethodDao->find($paymentMethodId);
        \ValidationHelper::entityExist($paymentMethod, \Pley\Entity\Payment\UserPaymentMethod::class);
        
        // This is just a validation for attack attempts, where a logged in user is trying to update
        // a different user's address by the address ID.
        if ($paymentMethod->getUserId() != $this->_user->getId()) {
            throw new \Exception('Mismatching relationship');
        }
        
        $that = $this;
        $this->_dbManager->transaction(function() use ($that, $paymentMethod) {
            $that->_setDefaultClosure($paymentMethod);
        });
        
        return \Response::json(['id' => $paymentMethod->getId()]);
    }
    
    // DELETE /user/payment-method/{$paymentMethodId}
    public function remove($paymentMethodId)
    {
        \RequestHelper::checkDeleteRequest();
        
        $paymentMethod = $this->_userPaymentMethodDao->find($paymentMethodId);
        \ValidationHelper::entityExist($paymentMethod, \Pley\Entity\Payment\UserPaymentMethod::class);
        
        // This is just a validation for attack attempts, where a logged in user is trying to update
        // a different user's address by the address ID.
        if ($paymentMethod->getUserId() != $this->_user->getId()) {
            throw new \Exception('Mismatching relationship');
        }
        
        // Making sure that it is not an attempt to disable the default payment method
        if ($this->_user->getDefaultPaymentMethodId() == $paymentMethod->getId()) {
            throw new \Pley\Exception\User\PaymentMethodDeleteException($this->_user, $paymentMethod);
        }
        
        $this->_userBillingMgr->removeCard($this->_user, $paymentMethod);
        
        return \Response::json(['id' => $paymentMethod->getId()]);
    }
    
    /**
     * Checks that the Credit Card data is correct and valid.
     * @param array $billingData Map with the Credit Card data
     * @return \Pley\Payment\Method\CreditCard
     */
    private function _validateBillingData(array $billingData)
    {
        $billingRules = [
            'ccNumber'       => 'required',
            'cvv'            => 'required',
            'expMonth'       => 'required',
            'expYear'        => 'required',
            'billingAddress' => 'required'
        ];
        \ValidationHelper::validate($billingData, $billingRules);
        
        $creditCard = new \Pley\Payment\Method\CreditCard(
            $billingData['ccNumber'], $billingData['expMonth'], $billingData['expYear']
        );
        $creditCard->setCVV($billingData['cvv']);
        
        $billingAddressData = $billingData['billingAddress'];
            
        // Billing Address can be either an Integer representing an User stored Address ID
        // or it can be a map with the specific billing address
        if (!is_numeric($billingAddressData)) {
            $billingAddressRules = [
                'street1'  => 'required',
                'street2'  => 'sometimes',
                'city'     => 'required|alpha_dot_space',
                'state'    => 'required|alpha',
                'country'  => 'required|alpha_space',
                'zip'      => 'required'
            ];
            \ValidationHelper::validate($billingAddressData, $billingAddressRules);
            
            $creditCard->setBillingAddress(new \Pley\Payment\Method\BillingAddress(
                $billingAddressData['street1'], 
                $billingAddressData['street2'],
                $billingAddressData['city'], 
                $billingAddressData['state'],
                $billingAddressData['zip'], 
                $billingAddressData['country']
            ));
        
        } else {
            $userAddressId = $billingAddressData;
            $userAddress   = $this->_userAddressDao->find($userAddressId);
            
            // This should not happen unless somebody is trying to forge an API call, so it is more
            // of a validation check than an actual runtime error check
            if ($this->_user->getId() != $userAddress->getUserId()) {
                throw new \Exception('Mismatching Relationship between User and Address');
            }
            
            $creditCard->setBillingAddress(new \Pley\Payment\Method\BillingAddress(
                $userAddress->getStreet1(), 
                $userAddress->getStreet2(),
                $userAddress->getCity(), 
                $userAddress->getState(),
                $userAddress->getZipCode(), 
                $userAddress->getCountry()
            ));
        }
        
        // Now validating with the Payment Vendor that the card is good to go.
        $this->_userBillingMgr->valdiateCard($this->_user, $creditCard);
        
        return $creditCard;
    }
    
    /**
     * Closure method to add a card to the user (or re-enables one previously deleted via the BillingManager) as a transaction.
     * @param \Pley\Payment\Method\CreditCard $creditCard
     * @return \Pley\Entity\Payment\UserPaymentMethod
     */
    private function _addClosure(\Pley\Payment\Method\CreditCard $creditCard)
    {
        $this->_dbManager->checkActiveTransaction(__METHOD__);
        
        $isFirstCard = false;
        
        // If the user has never added a payment method (i.e. a gift member), then we need to add
        // the account first.
        if (empty($this->_user->getVPaymentSystemId()) || $this->_user->getVPaymentSystemId() === \Pley\Enum\PaymentSystemEnum::PAYPAL) {
            $this->_userBillingMgr->addUserAccount($this->_user);
            $isFirstCard = true;
        }
        
        $paymentMethod = $this->_userBillingMgr->addCard($this->_user, $creditCard);
        
        if ($isFirstCard) {
            // Since this is the first card added to the vendor payment system and as such, the 
            // Default automatically, no need to make a new call to the vendor to set this Card as 
            // the Default one, just update the DB relationship.
            $this->_user->setDefaultPaymentMethodId($paymentMethod->getId());
            $this->_userDao->save($this->_user);
        }
        
        return $paymentMethod;
    }
    
    /**
     * Closure method to change the default card and update all the subscriptions relationships as a transaction
     * @param \Pley\Entity\Payment\UserPaymentMethod $paymentMethod
     */
    private function _setDefaultClosure(\Pley\Entity\Payment\UserPaymentMethod $paymentMethod)
    {
        $this->_dbManager->checkActiveTransaction(__METHOD__);
        
        // We need to update all the subscriptions that have a payment method associated to them to
        // this new Payment Method.
        $profileSubsList = $this->_profileSubsDao->findByUser($this->_user->getId());
        foreach ($profileSubsList as $profileSubscription) {
            // If it is a gift subscription, ignore it, no need to update
            if ($profileSubscription->getStatus() == \Pley\Enum\SubscriptionStatusEnum::GIFT) {
                continue;
            }

            $currentPaymentMethod = $this->_userPaymentMethodDao->find($profileSubscription->getUserPaymentMethodId());
            // Ignore PayPal based subscriptions, as they cannot change a payment method at all
            if($currentPaymentMethod->getVPaymentSystemId() === PaymentSystemEnum::PAYPAL){
                continue;
            }
            // Now update the payment method and save
            $profileSubscription->setUserPaymentMethodId($paymentMethod->getId());
            $this->_profileSubsDao->save($profileSubscription);
        }
        
        $this->_userBillingMgr->setDefaultCard($this->_user, $paymentMethod);
    }
}
