<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Payment;

/**
 * The <kbd>PaymentManagerInterface</kbd> defines the methods needed to interact with a vendor
 * Payment System.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Payment
 * @subpackage Payment
 */
interface PaymentManagerInterface
{
    /**
     * Retrun the Payment Vendor's minimum charge
     * @return float
     */
    public function getMinimumCharge();
    
    /**
     * Creates a new User in the Vendor Payment system and returns the respective User object.
     * <p>It updates the internal reference of the user to the vendor payment system.</p>
     * @param \Pley\Entity\User\User $user
     * @return \Pley\Payment\PaymentAccount
     */
    public function createUser(\Pley\Entity\User\User $user);
    
    /** 
     * Updates the email associated to the user account
     * @param \Pley\Entity\User\User $user
     */
    public function updateUserEmail(\Pley\Entity\User\User $user);
    
    /**
     * Checks whether the supplied card is valid.
     * @param \Pley\Entity\User\User          $user
     * @param \Pley\Payment\Method\CreditCard $card
     * @throws \Pley\Exception\Payment\PaymentMethodInvalidInputException If the card is invalid.
     */
    public function validateCard(\Pley\Entity\User\User $user, \Pley\Payment\Method\CreditCard $card);
    
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
    public function getCard(\Pley\Entity\User\User $user, $paymentMethod);
    
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
    public function getCardList(\Pley\Entity\User\User $user, $paymentMethodList = null);
    
    /**
     * Returns whether a credit card exists with the same number exists in the User's Account.
     * @param \Pley\Entity\User\User          $user
     * @param \Pley\Payment\Method\CreditCard $card
     * @return boolean <kbd>TRUE</kbd> if the user has this card already, <kbd>FALSE</kbd> otherwise.
     */
    public function isCardExists(\Pley\Entity\User\User $user, \Pley\Payment\Method\CreditCard $card);
    
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
            \Pley\Entity\User\User $user, $paymentMethodList, \Pley\Payment\Method\CreditCard $card);
    
    /**
     * Adds a credit card to the User's Payment Account.
     * @param \Pley\Entity\User\User          $user
     * @param \Pley\Payment\Method\CreditCard $card
     * @return \Pley\Payment\Method\CreditCard
     * @throws \Pley\Exception\Entity\ExistingPaymentMethodException if the CreditCard already exists.
     */
    public function addCard(\Pley\Entity\User\User $user, \Pley\Payment\Method\CreditCard $card);
    
    /**
     * Updates an existing's payment method expiration date.
     * <p>CVV is not needed as it is handled transparently by our vendor.</p>
     * @param \Pley\Entity\User\User $user
     * @param \Pley\Entity\Payment\UserPaymentMethod $paymentMethod
     * @param int                                    $expMonth
     * @param int                                    $expYear
     */
    public function updateCard(\Pley\Entity\User\User $user, \Pley\Entity\Payment\UserPaymentMethod $paymentMethod,
            $expMonth, $expYear);
    
    /**
     * Sets the supplied payment method as the Default for the given user.
     * @param \Pley\Entity\User\User                 $user
     * @param \Pley\Entity\Payment\UserPaymentMethod $paymentMethod
     */
    public function setDefaultCard(\Pley\Entity\User\User $user, \Pley\Entity\Payment\UserPaymentMethod $paymentMethod);
    
    /**
     * Makes a one-time single charge on the supplied card.
     * @param \Pley\Payment\Method\CreditCard $card
     * @param float                           $amount A positive value (> 0)
     * @param string                          $description   Detail string for this transaction.
     * @param array                           $metadata      (Optional)<br/>Key/Value paris to add 
     *      to the definition of this charge.
     * @return \Pley\Payment\Method\Transaction
     */
    public function singleCharge(\Pley\Payment\Method\CreditCard $card, $amount, $description, $metadata = null);
    
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
            $amount, $description, $metadata = null);
    
    /**
     * Returns the Subscription information from the vendor system.
     * @param \Pley\Entity\User\User                       $user
     * @param \Pley\Entity\Profile\ProfileSubscriptionPlan $subscriptionPlan
     * @return \Pley\Payment\Subscription
     */
    public function getSubscription(
            \Pley\Entity\User\User $user, \Pley\Entity\Profile\ProfileSubscriptionPlan $subscriptionPlan);
            
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
            $beginDate = null, $metadata = null);
    
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
            $cancelSource, $opUserId = null);
    
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
            $cancelSource, $opUserId);
    
    /**
     * Resets a subscription back to have auto renew.
     * @param \Pley\Entity\User\User $user
     * @param \Pley\Entity\Profile\ProfileSubscriptionPlan $subscriptionPlan
     * @return type
     */
    public function reactivateAutoRenew(
            \Pley\Entity\User\User $user, \Pley\Entity\Profile\ProfileSubscriptionPlan $subscriptionPlan);
    
    /**
     * Adds a credit towards the User's Billing Account Balance.
     * @param \Pley\Entity\User\User $user
     * @param \Pley\Entity\Profile\ProfileSubscriptionPlan $profileSubscriptionPlan
     * @param float                  $amount
     */
    public function addCredit(\Pley\Entity\User\User $user, \Pley\Entity\Profile\ProfileSubscriptionPlan $profileSubscriptionPlan, $amount);
}
