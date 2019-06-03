<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Payment;

/**
 * The <kbd>AbstractPaymentManager</kbd> is the Central implementation of the <kbd>PaymentManagerInterface</kbd>
 * that provides checks needed for each of the transactions and then delegates so that the specific
 * vendor implementation does not the final steps to
 * the specific Vendor implementations.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Payment
 * @subpackage Payment
 */
abstract class AbstractPaymentManager implements PaymentManagerInterface
{
    /**
     * Creates a new User in the Vendor Payment system and returns the respective User object.
     * <p>It updates the internal reference of the user to the vendor payment system.</p>
     * @param \Pley\Entity\User\User $user
     * @return \Pley\Payment\PaymentAccount
     */
    public function createUser(\Pley\Entity\User\User $user)
    {
        // Validation that the User object has an ID but has not been initialized with a vendor 
        // payment account.
        if (empty($user->getId())) {
            throw new \Exception('Invalid user object to create new Stripe account');
        }
        
        // Creating the Vendor Payment User
        $paymentUser = $this->_createUserDelegate($user);
        
        // Updating the User reference to this Payment User info
        $user->setVPaymentAccount($this->_getVendorSystemId(), $paymentUser->getVendorId());
        
        return $paymentUser;
    }
    
    /** 
     * Updates the email associated to the user account
     * @param \Pley\Entity\User\User $user
     */
    public function updateUserEmail(\Pley\Entity\User\User $user)
    {
        $this->_validateVendorAccount($user);
        $this->_updateUserEmailDelegate($user);
    }
    
    /**
     * Checks whether the supplied card is valid.
     * @param \Pley\Entity\User\User          $user
     * @param \Pley\Payment\Method\CreditCard $card
     * @throws \Pley\Exception\Payment\PaymentMethodInvalidInputException If the card is invalid.
     */
    public function validateCard(\Pley\Entity\User\User $user, \Pley\Payment\Method\CreditCard $card)
    {
        return $this->_validateCardDelegate($user, $card);
    }
    
    /**
     * Returns the Credit Card for the supplied PaymentMethod object.
     * <p>The payment method can be either a <kbd>UserPaymentMethod</kbd> object representing our 
     * reference to the vendor card or a <kbd>CreditCard</kbd> object representing a raw card that
     * exists on the vendor system and we need to retrieve vendor values for it.</p>
     * 
     * @param \Pley\Entity\User\User $user
     * @param \Pley\Entity\Payment\UserPaymentMethod|\Pley\Payment\Method\CreditCard $paymentMethod
     * @return \Pley\Payment\Method\CreditCard
     */
    public function getCard(\Pley\Entity\User\User $user, $paymentMethod)
    {
        //$this->_validateVendorAccount($user);
        
        // If the reference is of Payment Method (our object) then validate ownership
        if ($paymentMethod instanceof \Pley\Entity\Payment\UserPaymentMethod) {
            $this->_validateSystemId($paymentMethod->getVPaymentSystemId());
            $this->_validateUserOwnership($user, $paymentMethod);
            
        // Otherwise, if it is not of CreditCard type, throw an exception
        } else if (!$paymentMethod instanceof \Pley\Payment\Method\CreditCard){
            throw new \InvalidArgumentException('Unrecognized type for `$paymentMethod`.');
        }
        
        $creditCard = $this->_getCardDelegate($user, $paymentMethod);
        return $creditCard;
    }
    
    /**
     * Returns the list of Credit Cards for the supplied PaymentMethod objects.
     * @param \Pley\Entity\User\User $user
     * @param \Pley\Entity\Payment\UserPaymentMethod[] $paymentMethodList (Optional)<br/>Usually 
     *      supplied to match 1-to-1 the cards we have on file with the ones on the vendor system
     *      which usually they should be a 100% match unless an external change was made which caused
     *      a difference.<br/>
     *      If Not supplied, all the vendor cards will be retrieved.<br/>
     *      If supplied, all vendor cards will be filtered against the payment method list supplied.
     * @return \Pley\Payment\Method\CreditCard[]
     */
    public function getCardList(\Pley\Entity\User\User $user, $paymentMethodList = null)
    {
        $this->_validateVendorAccount($user);
        
        // Validate that all PaymentMethods are set for this implementation
        if (isset($paymentMethodList)) {
            foreach ($paymentMethodList as $paymentMethod) {
                $this->_validateSystemId($paymentMethod->getVPaymentSystemId());
                $this->_validateUserOwnership($user, $paymentMethod);
            }
        }
        
        $creditCardList = $this->_getCardListDelegate($user, $paymentMethodList);
        return $creditCardList;
    }
    
    /**
     * Returns whether a credit card exists with the same number exists in the User's Account.
     * @param \Pley\Entity\User\User          $user
     * @param \Pley\Payment\Method\CreditCard $card
     * @return boolean <kbd>TRUE</kbd> if the user has this card already, <kbd>FALSE</kbd> otherwise.
     */
    public function isCardExists(\Pley\Entity\User\User $user, \Pley\Payment\Method\CreditCard $card)
    {
        $this->_validateVendorAccount($user);
        
        $isCardExists = $this->_isCardExistsDelegate($user, $card);
        return $isCardExists;
    }
    
    /**
     * Returns the <kbd>UserPaymentMethod</kbd> that matches the supplied raw data card from the
     * user's registered payment methods.
     * <p>This will only return a value if <kbd>::isCardExists()</kbd> returns <kbd>TRUE</kbd>.</p>
     * 
     * @param \Pley\Entity\User\User                   $user
     * @param \Pley\Entity\Payment\UserPaymentMethod[] $paymentMethodList The list to choose from
     *      which the card details would match.
     * @param \Pley\Payment\Method\CreditCard          $card
     * @return \Pley\Entity\Payment\UserPaymentMethod
     */
    public function getExistingCard(
            \Pley\Entity\User\User $user, $paymentMethodList, \Pley\Payment\Method\CreditCard $card)
    {
        //$this->_validateVendorAccount($user);
        
        // Validate that all PaymentMethods are set for this implementation
        if (isset($paymentMethodList)) {
            foreach ($paymentMethodList as $paymentMethod) {
                $this->_validateSystemId($paymentMethod->getVPaymentSystemId());
                $this->_validateUserOwnership($user, $paymentMethod);
            }
        }
        
        $paymentMethod = $this->_getExistingCardDelegate($user, $paymentMethodList, $card);
        
        return $paymentMethod;
    }
    
    /**
     * Adds a credit card to the User's Payment Account.
     * @param \Pley\Entity\User\User          $user
     * @param \Pley\Payment\Method\CreditCard $card
     * @return \Pley\Payment\Method\CreditCard
     * @throws \Pley\Exception\Entity\ExistingPaymentMethodException if the CreditCard already exists.
     */
    public function addCard(\Pley\Entity\User\User $user, \Pley\Payment\Method\CreditCard $card)
    {
        $this->_validateVendorAccount($user);
        
        if (empty($card->getCVV()) || empty($card->getBillingAddress())) {
            throw new \InvalidArgumentException('Card is missing either the CVV or the Billing Address');
        }
        
        $creditCard = $this->_addCardDelegate($user, $card);
        return $creditCard;
    }
    
    /**
     * Updates an existing's payment method expiration date.
     * <p>CVV is not needed as it is handled transparently by our vendor.</p>
     * @param \Pley\Entity\User\User $user
     * @param \Pley\Entity\Payment\UserPaymentMethod $paymentMethod
     * @param int                                    $expMonth
     * @param int                                    $expYear
     */
    public function updateCard(\Pley\Entity\User\User $user, \Pley\Entity\Payment\UserPaymentMethod $paymentMethod,
            $expMonth, $expYear)
    {
        $this->_validateVendorAccount($user);
        $this->_validateSystemId($paymentMethod->getVPaymentSystemId());
        $this->_validateUserOwnership($user, $paymentMethod);
        
        $this->_updateCardDelegate($user, $paymentMethod, $expMonth, $expYear);
    }
    
    /**
     * Sets the supplied payment method as the Default for the given user.
     * @param \Pley\Entity\User\User                 $user
     * @param \Pley\Entity\Payment\UserPaymentMethod $paymentMethod
     */
    public function setDefaultCard(\Pley\Entity\User\User $user, \Pley\Entity\Payment\UserPaymentMethod $paymentMethod)
    {
        $this->_validateVendorAccount($user);
        $this->_validateSystemId($paymentMethod->getVPaymentSystemId());
        $this->_validateUserOwnership($user, $paymentMethod);
        
        $this->_setDefaultCardDelegate($user, $paymentMethod);
        
        $user->setDefaultPaymentMethodId($paymentMethod->getId());
    }
    
    /**
     * Makes a one-time single charge on the supplied card.
     * @param \Pley\Payment\Method\CreditCard $card
     * @param float                           $amount A positive value (> 0)
     * @param string                          $description   Detail string for this transaction.
     * @param array                           $metadata      (Optional)<br/>Key/Value paris to add 
     *      to the definition of this charge.
     * @return \Pley\Payment\Method\Transaction
     */
    public function singleCharge(\Pley\Payment\Method\CreditCard $card, $amount, $description, $metadata = null)
    {
        // Validating input
        if (!is_numeric($amount) || $amount <= 0 || !is_string($description) || empty($description)) {
            throw new \InvalidArgumentException('Either the `$amount` or `$description` argument is invalid');
        }
        
        $transaction = $this->_singleChargeDelegate($card, $amount, $description, $metadata);
        return $transaction;
    }
    
    /**
     * Makes a charge for the user with the given payment method.
     * @param \Pley\Entity\User\User                 $user
     * @param \Pley\Entity\Payment\UserPaymentMethod $paymentMethod
     * @param float                                  $amount        A positive value (> 0)
     * @param string                                 $description   Detail string for this transaction.
     * @param array                                  $metadata      (Optional)<br/>Key/Value paris 
     *      to add to the definition of this charge.
     * @return \Pley\Payment\Method\Transaction
     */
    public function charge(
            \Pley\Entity\User\User $user, \Pley\Entity\Payment\UserPaymentMethod $paymentMethod, 
            $amount, $description, $metadata = null)
    {
        $this->_validateVendorAccount($user);
        $this->_validateSystemId($paymentMethod->getVPaymentSystemId());
        $this->_validateUserOwnership($user, $paymentMethod);
        
        // Due to Stripe's limitation on Subscriptions only being charged on the Default Card,
        // we are adding a check here so that only the default one is used for charging as well to
        // keep everything in sync.
        if ($user->getDefaultPaymentMethodId() != $paymentMethod->getId()) {
            throw new Exception('Payment Method is not the Default one');
        }

        $minChargeAmount = $this->getMinimumCharge();
        
        // Validating input
        if (!is_numeric($amount) || $amount < $minChargeAmount || !is_string($description) || empty($description)) {
            throw new \InvalidArgumentException('Either the `$amount` or `$description` argument is invalid');
        }
        
        $transaction = $this->_chargeDelegate($user, $paymentMethod, $amount, $description, $metadata);
        return $transaction;
    }
    
    /**
     * Returns the Subscription information from the vendor system.
     * @param \Pley\Entity\User\User                       $user
     * @param \Pley\Entity\Profile\ProfileSubscriptionPlan $subscriptionPlan
     * @return \Pley\Payment\Subscription
     */
    public function getSubscription(
            \Pley\Entity\User\User $user, \Pley\Entity\Profile\ProfileSubscriptionPlan $subscriptionPlan)
    {
        //$this->_validateVendorAccount($user);
        $this->_validateSystemId($subscriptionPlan->getVPaymentSystemId());
        $this->_validateUserOwnership($user, $subscriptionPlan);
        
        $subscription = $this->_getSubscriptionDelegate($user, $subscriptionPlan);
        return $subscription;
    }
    
    /**
     * Creates a new subscription to begin on a future day on a given PaymentPlan on the user's
     * Default payment method.
     * @param \Pley\Entity\User\User                 $user
     * @param \Pley\Entity\Payment\VendorPaymentPlan $vPaymentPlan
     * @param int                                    $beginDate    (Optional)
     * @param array                                  $metadata     (Optional)<br/>Key/Value paris 
     * @return \Pley\Payment\Subscription
     */
    public function addSubscription(\Pley\Entity\User\User $user, \Pley\Entity\Payment\VendorPaymentPlan $vPaymentPlan, 
            $beginDate = null, $metadata = null)
    {
        $this->_validateVendorAccount($user);
        $this->_validateSystemId($vPaymentPlan->getVPaymentSystemId());
        
        $subscription = $this->_addSubscriptionDelegate($user, $vPaymentPlan, $beginDate, $metadata);
        return $subscription;
    }
    
    /**
     * Stops the subscription from auto-renewing at the end of the billing period.
     * @param \Pley\Entity\User\User                       $user
     * @param \Pley\Entity\Profile\ProfileSubscriptionPlan $subscriptionPlan
     * @param int                                          $cancelSource
     * @param int                                          $opUserId         (Optional)<br/>If stopped
     *      by a Customer Service agent.
     */
    public function stopSubscriptionAutoRenew(
            \Pley\Entity\User\User $user, \Pley\Entity\Profile\ProfileSubscriptionPlan $subscriptionPlan, 
            $cancelSource, $opUserId = null)
    {
        $this->_validateSystemId($subscriptionPlan->getVPaymentSystemId());
        $this->_validateUserOwnership($user, $subscriptionPlan);
        
        \Pley\Enum\SubscriptionCancelSourceEnum::validate($cancelSource);
        
        $paymentSubscription = $this->_stopSubscriptionAutoRenewDelegate($user, $subscriptionPlan);
        
        $periodEndAt = $paymentSubscription->getPeriodDateEnd();
        $subscriptionPlan->stopAutoRenewal($cancelSource, $periodEndAt, $opUserId);
    }

    /**
     * Pauses given subscription by setting a trial date start and trial date end to it.
     * Used with a skip a box feature.
     * @param \Pley\Entity\User\User                       $user
     * @param \Pley\Entity\Profile\ProfileSubscriptionPlan $subscriptionPlan
     * @param int                                          $pauseDateEnd
     */
    public function subscriptionPause(
        \Pley\Entity\User\User $user,
        \Pley\Entity\Profile\ProfileSubscriptionPlan $subscriptionPlan,
        $pauseDateEnd)
    {
        $this->_validateSystemId($subscriptionPlan->getVPaymentSystemId());
        $this->_validateUserOwnership($user, $subscriptionPlan);

        $this->_pauseSubscriptionDelegate(
            $user, $subscriptionPlan,
            $pauseDateEnd);
    }

    /**
     * Stops the subscription from auto-renewing and flags it as cancelled.
     * <p>This operation is only to be performed by a Customer Service user, not by a customer.</p>
     * <p>Note: Unlike <kbd>stopSubscriptionAutoRenew</kbd> it is done at the current date and not
     * at the end of the billing period.</p>
     * @param \Pley\Entity\User\User                       $user
     * @param \Pley\Entity\Profile\ProfileSubscriptionPlan $subscriptionPlan
     * @param int                                          $cancelSource
     * @param int                                          $opUserId
     */
    public function subscriptionCancel(
            \Pley\Entity\User\User $user, \Pley\Entity\Profile\ProfileSubscriptionPlan $subscriptionPlan, 
            $cancelSource, $opUserId)
    {
        $this->_validateSystemId($subscriptionPlan->getVPaymentSystemId());
        $this->_validateUserOwnership($user, $subscriptionPlan);
        
        \Pley\Enum\SubscriptionCancelSourceEnum::validate($cancelSource);
        
        $paymentSubscription = $this->_subscriptionCancelDelegate($user, $subscriptionPlan);
        
        $cancelAt = $paymentSubscription->getCancelAt();
        $subscriptionPlan->cancel($cancelSource, $cancelAt, $opUserId);
    }
    
    /**
     * Resets a subscription back to have auto renew.
     * @param \Pley\Entity\User\User $user
     * @param \Pley\Entity\Profile\ProfileSubscriptionPlan $subscriptionPlan
     * @return type
     */
    public function reactivateAutoRenew(
            \Pley\Entity\User\User $user, \Pley\Entity\Profile\ProfileSubscriptionPlan $subscriptionPlan)
    {
        $this->_validateVendorAccount($user);
        $this->_validateSystemId($subscriptionPlan->getVPaymentSystemId());
        $this->_validateUserOwnership($user, $subscriptionPlan);
        
        $subscription = $this->_reactivateAutoRenewDelegate($user, $subscriptionPlan);
        return $subscription;
    }
    
    /**
     * Adds a credit towards the User's Billing Account Balance.
     * @param \Pley\Entity\User\User $user
     * @param \Pley\Entity\Profile\ProfileSubscriptionPlan $profileSubscriptionPlan
     * @param float                  $amount
     * @param string                 $description
     */
    public function addCredit(\Pley\Entity\User\User $user, \Pley\Entity\Profile\ProfileSubscriptionPlan $profileSubscriptionPlan, $amount, $description = '')
    {
        $this->_validateVendorAccount($user);
        
        $this->_addCreditDelegate( $user, $profileSubscriptionPlan, $amount, $description);
    }

    /**
     * Adds a credit towards the User's Billing Account Balance.
     * @param \Pley\Entity\User\User $user
     * @return \Pley\Payment\CreditData[]
     */
    public function getCreditInfo(\Pley\Entity\User\User $user)
    {
        $this->_validateVendorAccount($user);
        return $this->_getCreditInfoDelegate($user);
    }
            
    // ---------------------------------------------------------------------------------------------
    // SPECIAL METHOD TO DELETE A CARD, NOT JUST HIDE IT, IT IS NOT VISIBLE AS IT NOT INTENDED FOR
    // ANY USE OTHER THAN WHEN ADDING THE VERY FIRST CARD FOR THE VERY FIRST PAID SUBSCRIPTION FAILS
    protected function _deleteFirstCard(\Pley\Entity\User\User $user, \Pley\Entity\Payment\UserPaymentMethod $paymentMethod)
    {
        $this->_validateVendorAccount($user);
        $this->_validateSystemId($paymentMethod->getVPaymentSystemId());
        $this->_validateUserOwnership($user, $paymentMethod);
        
        $this->_deleteFirstCardDelegate($user, $paymentMethod);
    }
    
    // ---------------------------------------------------------------------------------------------
    // Protected Methods ---------------------------------------------------------------------------
    
    /**
     * Checks that the supplied Vendor Payment System ID matches the implementation.
     * @param int $systemId
     * @throws \Pley\Exception\Payment\PaymentSystemMismatchingException If the IDs doesn't match
     */
    protected function _validateSystemId($systemId)
    {
        if ($this->_getVendorSystemId() != $systemId) {
            throw new \Pley\Exception\Payment\PaymentSystemMismatchingException(
                $this->_getVendorSystemId(), $systemId
            );
        }
    }
    
    /**
     * Return the vendor ID for this implementation
     * @return int
     */
    protected abstract function _getVendorSystemId();
    
    /**
     * Delegate method that creates a new User in the Vendor Payment system and returns the respective
     * Payment User object.
     * @param \Pley\Entity\User\User $user
     * @return \Pley\Payment\PaymentAccount
     */
    protected abstract function _createUserDelegate(\Pley\Entity\User\User $user);
    
    /** 
     * Delegate method that updates the email associated to the user account in the Vendor Payment system.
     * @param \Pley\Entity\User\User $user
     */
    protected abstract function _updateUserEmailDelegate(\Pley\Entity\User\User $user);
    
    /**
     * Delegate method that checks whether the supplied card is valid.
     * @param \Pley\Entity\User\User          $user
     * @param \Pley\Payment\Method\CreditCard $card
     * @throws \Pley\Exception\Payment\PaymentMethodInvalidInputException If the card is invalid.
     */
    protected abstract function _validateCardDelegate(\Pley\Entity\User\User $user, \Pley\Payment\Method\CreditCard $card);
    
    /**
     * Delegate method that returns the Credit Card for the supplied PaymentMethod object.
     * <p>The payment method can be either a <kbd>UserPaymentMethod</kbd> object representing our 
     * reference to the vendor card or a <kbd>CreditCard</kbd> object representing a raw card that
     * exists on the vendor system and we need to retrieve vendor values for it.</p>
     * 
     * @param \Pley\Entity\User\User $user
     * @param \Pley\Entity\Payment\UserPaymentMethod|\Pley\Payment\Method\CreditCard $paymentMethod
     * @return \Pley\Payment\Method\CreditCard
     */
    protected abstract function _getCardDelegate(\Pley\Entity\User\User $user, $paymentMethod);
    
    /**
     * Delegate method that returns the list of Credit Cards for the supplied PaymentMethod objects.
     * @param \Pley\Entity\User\User $user
     * @param \Pley\Entity\Payment\UserPaymentMethod[] $paymentMethodList (Optional)<br/>Usually 
     *      supplied to match 1-to-1 the cards we have on file with the ones on the vendor system
     *      which usually they should be a 100% match unless an external change was made which caused
     *      a difference.<br/>
     *      If Not supplied, all the vendor cards will be retrieved.<br/>
     *      If supplied, all vendor cards will be filtered against the payment method list supplied.
     * @return \Pley\Payment\Method\CreditCard[]
     */
    protected abstract function _getCardListDelegate(\Pley\Entity\User\User $user, $paymentMethodList = null);
    
    /**
     * Delegate method that returns whether a credit card exists with the same number exists in the User's Account.
     * @param \Pley\Entity\User\User          $user
     * @param \Pley\Payment\Method\CreditCard $card
     * @return boolean <kbd>TRUE</kbd> if the user has this card already, <kbd>FALSE</kbd> otherwise.
     */
    protected abstract function _isCardExistsDelegate(\Pley\Entity\User\User $user, \Pley\Payment\Method\CreditCard $card);
    
    /**
     * Delegate method that returns the <kbd>UserPaymentMethod</kbd> that matches the supplied raw 
     * data card from the user's registered payment methods.
     * <p>This will only return a value if <kbd>::isCardExists()</kbd> returns <kbd>TRUE</kbd>.</p>
     * 
     * @param \Pley\Entity\User\User                   $user
     * @param \Pley\Entity\Payment\UserPaymentMethod[] $paymentMethodList The list to choose from
     *      which the card details would match.
     * @param \Pley\Payment\Method\CreditCard          $card
     * @return \Pley\Entity\Payment\UserPaymentMethod
     */
    protected abstract function _getExistingCardDelegate(
            \Pley\Entity\User\User $user, $paymentMethodList, \Pley\Payment\Method\CreditCard $card);
    
    /**
     * Delegate method that adds a credit card to the User's Payment Account
     * @param \Pley\Entity\User\User          $user
     * @param \Pley\Payment\Method\CreditCard $card
     * @return \Pley\Payment\Method\CreditCard
     * @throws \Pley\Exception\Entity\ExistingPaymentMethodException if CreditCard already exists
     */
    protected abstract function _addCardDelegate(\Pley\Entity\User\User $user, \Pley\Payment\Method\CreditCard $card);
    
    /**
     * Delegate method that updates an existing's payment method expiration date.
     * @param \Pley\Entity\User\User $user
     * @param \Pley\Entity\Payment\UserPaymentMethod $paymentMethod
     * @param int                                    $expMonth
     * @param int                                    $expYear
     */
    protected abstract function _updateCardDelegate(
            \Pley\Entity\User\User $user, \Pley\Entity\Payment\UserPaymentMethod $paymentMethod, $expMonth, $expYear);
    
    /**
     * Delegate method that sets the supplied payment method as the Default for the given user.
     * @param \Pley\Entity\User\User                 $user
     * @param \Pley\Entity\Payment\UserPaymentMethod $paymentMethod
     */
    protected abstract function _setDefaultCardDelegate(
            \Pley\Entity\User\User $user, \Pley\Entity\Payment\UserPaymentMethod $paymentMethod);
    
    /**
     * Delegate method that makes a one-time single charge on the supplied card.
     * @param \Pley\Payment\Method\CreditCard $card
     * @param float                           $amount A positive value (> 0)
     * @param string                          $description   Detail string for this transaction.
     * @param array                           $metadata      (Optional)<br/>Key/Value paris to add 
     *      to the definition of this charge.
     * @return \Pley\Payment\Method\Transaction
     */
    protected abstract function _singleChargeDelegate(
            \Pley\Payment\Method\CreditCard $card, $amount, $description, $metadata = null);
    
    /**
     * Delegate method that makes a charge for the user with the given payment method.
     * @param \Pley\Entity\User\User                 $user
     * @param \Pley\Entity\Payment\UserPaymentMethod $paymentMethod
     * @param float                                  $amount
     * @param string                                 $description   Detail string for this transaction.
     * @param array                                  $metadata      (Optional)<br/>Key/Value paris 
     *      to add to the definition of this charge.
     * @return \Pley\Payment\Method\Transaction
     */
    protected abstract function _chargeDelegate(
            \Pley\Entity\User\User $user, \Pley\Entity\Payment\UserPaymentMethod $paymentMethod, 
            $amount, $description, $metadata = null);
    
    /**
     * Delegate method that returns the Subscription information from the vendor system.
     * @param \Pley\Entity\User\User                       $user
     * @param \Pley\Entity\Profile\ProfileSubscriptionPlan $subscriptionPlan
     * @return \Pley\Payment\Subscription
     */
    protected abstract function _getSubscriptionDelegate(
            \Pley\Entity\User\User $user, \Pley\Entity\Profile\ProfileSubscriptionPlan $subscriptionPlan);
    
    /**
     * Delegate method that creates a new subscription to begin on a future day on a given PaymentPlan.
     * @param \Pley\Entity\User\User                 $user
     * @param \Pley\Entity\Payment\VendorPaymentPlan $vPaymentPlan
     * @param int                                    $beginDate    (Optional)
     * @param array                                  $metadata     (Optional)<br/>Key/Value paris 
     * @return \Pley\Payment\Subscription
     */
    protected abstract function _addSubscriptionDelegate(\Pley\Entity\User\User $user, \Pley\Entity\Payment\VendorPaymentPlan $vPaymentPlan, 
            $beginDate = null, $metadata = null);
    
    /**
     * Delegate method that stops the subscription from auto-renewing at the end of the billing period.
     * @param \Pley\Entity\User\User                       $user
     * @param \Pley\Entity\Profile\ProfileSubscriptionPlan $subscriptionPlan
     * @return \Pley\Payment\Subscription
     */
    protected abstract function _stopSubscriptionAutoRenewDelegate(
            \Pley\Entity\User\User $user, \Pley\Entity\Profile\ProfileSubscriptionPlan $subscriptionPlan);

    /**
     * Delegate method that pauses subscription for a certain amount of time
     * @param \Pley\Entity\User\User                       $user
     * @param \Pley\Entity\Profile\ProfileSubscriptionPlan $subscriptionPlan
     * @param int $pauseDateEnd
     * @return \Pley\Payment\Subscription
     */

    protected abstract function _pauseSubscriptionDelegate(
        \Pley\Entity\User\User $user,
        \Pley\Entity\Profile\ProfileSubscriptionPlan $subscriptionPlan,
        $pauseDateEnd);

    /**
     * Delegate method that stops the subscription from auto-renewing and flags it as cancelled.
     * @param \Pley\Entity\User\User                       $user
     * @param \Pley\Entity\Profile\ProfileSubscriptionPlan $subscriptionPlan
     * @return \Pley\Payment\Subscription
     */
    protected abstract function _subscriptionCancelDelegate(
            \Pley\Entity\User\User $user, \Pley\Entity\Profile\ProfileSubscriptionPlan $subscriptionPlan);
    
    /**
     * Delegate method that resets a subscription back to have auto renew.
     * @param \Pley\Entity\User\User $user
     * @param \Pley\Entity\Profile\ProfileSubscriptionPlan $subscriptionPlan
     * @return type
     */
    protected abstract function _reactivateAutoRenewDelegate(
            \Pley\Entity\User\User $user, \Pley\Entity\Profile\ProfileSubscriptionPlan $subscriptionPlan);
    
    /**
     * Delegate method that adds a credit towards the User's Billing Account Balance.
     * @param \Pley\Entity\User\User $user
     * @param \Pley\Entity\Profile\ProfileSubscriptionPlan $profileSubscriptionPlan
     * @param float                  $amount
     */
    protected abstract function _addCreditDelegate(\Pley\Entity\User\User $user, \Pley\Entity\Profile\ProfileSubscriptionPlan $profileSubscriptionPlan, $amount);

    /**
     * Delegate method that gets a credit information towards the User's Billing Account Balance.
     * @param \Pley\Entity\User\User $user
     * @return \Pley\Payment\CreditData[]
     */
    protected abstract function _getCreditInfoDelegate(\Pley\Entity\User\User $user);
    
    // ---------------------------------------------------------------------------------------------
    // Private Methods -----------------------------------------------------------------------------
    
    /**
     * Checks that the User object has a reference to the vendor payment account and throws an 
     * Exception if it doesn't.
     * @param \Pley\Entity\User\User $user
     * @throws \Exception
     */
    private function _validateVendorAccount(\Pley\Entity\User\User $user)
    {
        if (empty($user->getVPaymentAccountId())) {
            throw new \Exception('User does not have a Vendor Payment Account');
        }
        
        $this->_validateSystemId($user->getVPaymentSystemId());
    }
    
    /**
     * Checks that the supplied entity belongs to the given user.
     * @param \Pley\Entity\User\User $user
     * @param object                 $entity
     * @throws \Exception If the entity has a different UserID than the User's ID
     */
    private function _validateUserOwnership(\Pley\Entity\User\User $user, $entity)
    {
        if ($user->getId() != $entity->getUserId()) {
            $entityClass = get_class($entity);
            throw new \Exception("Entity `{$entityClass}` does not belong to supplied User.");
        }
    }
}
