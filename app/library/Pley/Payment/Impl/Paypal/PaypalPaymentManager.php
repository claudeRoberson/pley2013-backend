<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Payment\Impl\Paypal;

/**
 * The <kbd>PaypalPaymentManager</kbd> is the specific vendor implementation of the
 * <kbd>PaymentManagerInterface</kbd> for the PAYPAL payments system.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Payment.Impl.Paypal
 * @subpackage Payment
 */
class PaypalPaymentManager extends \Pley\Payment\AbstractPaymentManager implements \Pley\Payment\PaymentManagerInterface
{
    /**
     * Retrun the Payment Vendor's minimum charge
     * @return float
     */
    public function getMinimumCharge()
    {
        return 0.5;
    }

    /**
     * Return the vendor ID for this implementation
     * @return int
     */
    protected function _getVendorSystemId()
    {
        return \Pley\Enum\PaymentSystemEnum::PAYPAL;
    }

    /**
     * Creates a new User in the Vendor Payment system and returns the respective User object.
     * <p>It updates the internal reference of the user to the vendor payment system.</p>
     * @param \Pley\Entity\User\User $user
     * @return \Pley\Payment\PaymentAccount
     */
    protected function _createUserDelegate(\Pley\Entity\User\User $user)
    {
        //TODO: not used with a PayPal payment flow. Should be removed after abstract class refactoring
    }

    /**
     * Delegate method that updates the email associated to the user account in the Vendor Payment system.
     * @param \Pley\Entity\User\User $user
     */
    protected function _updateUserEmailDelegate(\Pley\Entity\User\User $user)
    {
        //TODO: not used with a PayPal payment flow. Should be removed after abstract class refactoring
    }

    /**
     * Delegate method that checks whether the supplied card is valid.
     * @param \Pley\Entity\User\User $user
     * @param \Pley\Payment\Method\CreditCard $card
     * @throws \Pley\Exception\Payment\PaymentMethodInvalidInputException If the card is invalid.
     */
    protected function _validateCardDelegate(\Pley\Entity\User\User $user, \Pley\Payment\Method\CreditCard $card)
    {
        //TODO: not used with a PayPal payment flow. Should be removed after abstract class refactoring
    }

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
    protected function _getCardDelegate(\Pley\Entity\User\User $user, $paymentMethod)
    {
        $creditCard = new \Pley\Payment\Method\CreditCard(
            '0000', '00', '00'
        );
        $creditCard->setBrand(\Pley\Enum\PaymentMethodBrandEnum::PAYPAL);
        $creditCard->setType('PayPal');
        return $creditCard;
    }

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
    protected function _getCardListDelegate(\Pley\Entity\User\User $user, $paymentMethodList = null)
    {
        //TODO: not used with a PayPal payment flow. Should be removed after abstract class refactoring
    }

    /**
     * Delegate method that returns whether a credit card exists with the same number exists in the User's Account.
     * @param \Pley\Entity\User\User $user
     * @param \Pley\Payment\Method\CreditCard $card
     * @return boolean <kbd>TRUE</kbd> if the user has this card already, <kbd>FALSE</kbd> otherwise.
     */
    protected function _isCardExistsDelegate(\Pley\Entity\User\User $user, \Pley\Payment\Method\CreditCard $card)
    {
        //TODO: not used with a PayPal payment flow. Should be removed after abstract class refactoring
    }

    /**
     * Delegate method that returns the <kbd>UserPaymentMethod</kbd> that matches the supplied raw
     * data card from the user's registered payment methods.
     * <p>This will only return a value if <kbd>::isCardExists()</kbd> returns <kbd>TRUE</kbd>.</p>
     *
     * @param \Pley\Entity\User\User $user
     * @param \Pley\Entity\Payment\UserPaymentMethod[] $paymentMethodList The list to choose from
     *      which the card details would match.
     * @param \Pley\Payment\Method\CreditCard $card
     * @return \Pley\Entity\Payment\UserPaymentMethod
     */
    protected function _getExistingCardDelegate(
        \Pley\Entity\User\User $user, $paymentMethodList, \Pley\Payment\Method\CreditCard $card)
    {
        //TODO: not used with a PayPal payment flow. Should be removed after abstract class refactoring
    }

    /**
     * Delegate method that adds a credit card to the User's Payment Account
     * @param \Pley\Entity\User\User $user
     * @param \Pley\Payment\Method\CreditCard $card
     * @return \Pley\Payment\Method\CreditCard
     * @throws \Pley\Exception\Entity\ExistingPaymentMethodException if CreditCard already exists
     */
    protected function _addCardDelegate(\Pley\Entity\User\User $user, \Pley\Payment\Method\CreditCard $card)
    {
        //TODO: not used with a PayPal payment flow. Should be removed after abstract class refactoring
    }

    /**
     * Delegate method that updates an existing's payment method expiration date.
     * @param \Pley\Entity\User\User $user
     * @param \Pley\Entity\Payment\UserPaymentMethod $paymentMethod
     * @param int $expMonth
     * @param int $expYear
     */
    protected function _updateCardDelegate(
        \Pley\Entity\User\User $user, \Pley\Entity\Payment\UserPaymentMethod $paymentMethod, $expMonth, $expYear)
    {
        //TODO: not used with a PayPal payment flow. Should be removed after abstract class refactoring
    }

    /**
     * Delegate method that sets the supplied payment method as the Default for the given user.
     * @param \Pley\Entity\User\User $user
     * @param \Pley\Entity\Payment\UserPaymentMethod $paymentMethod
     */
    protected function _setDefaultCardDelegate(
        \Pley\Entity\User\User $user, \Pley\Entity\Payment\UserPaymentMethod $paymentMethod)
    {
        //TODO: not used with a PayPal payment flow. Should be removed after abstract class refactoring
    }

    /**
     * Delegate method that makes a one-time single charge on the supplied card.
     * @param \Pley\Payment\Method\CreditCard $card
     * @param float $amount A positive value (> 0)
     * @param string $description Detail string for this transaction.
     * @param array $metadata (Optional)<br/>Key/Value paris to add
     *      to the definition of this charge.
     * @return \Pley\Payment\Method\Transaction
     */
    protected function _singleChargeDelegate(
        \Pley\Payment\Method\CreditCard $card, $amount, $description, $metadata = null)
    {
        //TODO: not used with a PayPal payment flow. Should be removed after abstract class refactoring
    }

    /**
     * Delegate method that makes a charge for the user with the given payment method.
     * @param \Pley\Entity\User\User $user
     * @param \Pley\Entity\Payment\UserPaymentMethod $paymentMethod
     * @param float $amount
     * @param string $description Detail string for this transaction.
     * @param array $metadata (Optional)<br/>Key/Value paris
     *      to add to the definition of this charge.
     * @return \Pley\Payment\Method\Transaction
     */
    protected function _chargeDelegate(
        \Pley\Entity\User\User $user, \Pley\Entity\Payment\UserPaymentMethod $paymentMethod,
        $amount, $description, $metadata = null)
    {
        //TODO: not used with a PayPal payment flow. Should be removed after abstract class refactoring
    }

    /**
     * Delegate method that returns the Subscription information from the vendor system.
     * @param \Pley\Entity\User\User $user
     * @param \Pley\Entity\Profile\ProfileSubscriptionPlan $subscriptionPlan
     * @return \Pley\Payment\Subscription
     */
    protected function _getSubscriptionDelegate(
        \Pley\Entity\User\User $user, \Pley\Entity\Profile\ProfileSubscriptionPlan $subscriptionPlan)
    {
        /**
         * @var $paypalManager \Pley\Billing\PaypalManager
         */
        $paypalManager = app('\Pley\Billing\PaypalManager');
        $agreement = $paypalManager->getBillingAgreement($subscriptionPlan->getVPaymentSubscriptionId());
        $metadata = new \stdClass();

        $startTime = \DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $agreement->getStartDate());
        $nextBillingTime = \DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $agreement->getAgreementDetails()->getNextBillingDate());

        $metadata->current_period_start = $startTime->getTimestamp();
        $metadata->canceled_at = $agreement->getAgreementDetails()->getFinalPaymentDate();
        $metadata->current_period_end = ($nextBillingTime) ? $nextBillingTime->getTimestamp() : null ;

        $paymentSubscription = new \Pley\Payment\Subscription($user);
        $paymentSubscription->initVendor(
            $this->_getVendorSystemId(), $agreement->getId(), $metadata
        );
        return $paymentSubscription;
    }

    /**
     * Delegate method that creates a new subscription to begin on a future day on a given PaymentPlan.
     * @param \Pley\Entity\User\User $user
     * @param \Pley\Entity\Payment\VendorPaymentPlan $vPaymentPlan
     * @param int $beginDate (Optional)
     * @param array $metadata (Optional)<br/>Key/Value paris
     * @return \Pley\Payment\Subscription
     */
    protected function _addSubscriptionDelegate(\Pley\Entity\User\User $user, \Pley\Entity\Payment\VendorPaymentPlan $vPaymentPlan,
                                                $beginDate = null, $metadata = null)
    {
        //TODO: not used with a PayPal payment flow. Should be removed after abstract class refactoring
    }

    /**
     * Delegate method that stops the subscription from auto-renewing at the end of the billing period.
     * @param \Pley\Entity\User\User $user
     * @param \Pley\Entity\Profile\ProfileSubscriptionPlan $subscriptionPlan
     * @return \Pley\Payment\Subscription
     */
    protected function _stopSubscriptionAutoRenewDelegate(
        \Pley\Entity\User\User $user, \Pley\Entity\Profile\ProfileSubscriptionPlan $subscriptionPlan)
    {
        /**
         * @var $paypalManager \Pley\Billing\PaypalManager
         */
        $paypalManager = app('\Pley\Billing\PaypalManager');

        $agreement = $paypalManager->getBillingAgreement($subscriptionPlan->getVPaymentSubscriptionId());
        $metadata = new \stdClass();

        $startTime = \DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $agreement->getStartDate());
        $nextBillingTime = \DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $agreement->getAgreementDetails()->getNextBillingDate());

        $metadata->current_period_start = $startTime->getTimestamp();
        $metadata->canceled_at = ($nextBillingTime) ? $nextBillingTime->getTimestamp() : null;
        $metadata->current_period_end = ($nextBillingTime) ? $nextBillingTime->getTimestamp() : null;

        $paymentSubscription = new \Pley\Payment\Subscription($user);
        $paymentSubscription->initVendor(
            $this->_getVendorSystemId(), $agreement->getId(), $metadata
        );

        $paypalManager->suspendBillingAgreement($subscriptionPlan->getVPaymentSubscriptionId());

        return $paymentSubscription;
    }

    /**
     * Delegate method that pauses subscription for a certain amount of time
     * @param \Pley\Entity\User\User $user
     * @param \Pley\Entity\Profile\ProfileSubscriptionPlan $subscriptionPlan
     * @param int $pauseDateEnd
     * @return \Pley\Payment\Subscription
     * @throws \Exception
     */

    protected function _pauseSubscriptionDelegate(
        \Pley\Entity\User\User $user, \Pley\Entity\Profile\ProfileSubscriptionPlan $subscriptionPlan, $pauseDateEnd)
    {
        /**
         * @var $paypalManager \Pley\Billing\PaypalManager
         */
        $paypalManager = app('\Pley\Billing\PaypalManager');
        $paypalManager->suspendBillingAgreement($subscriptionPlan->getVPaymentSubscriptionId());
    }

    /**
     * Delegate method that stops the subscription from auto-renewing and flags it as cancelled.
     * @param \Pley\Entity\User\User $user
     * @param \Pley\Entity\Profile\ProfileSubscriptionPlan $subscriptionPlan
     * @return \Pley\Payment\Subscription
     */
    protected function _subscriptionCancelDelegate(
        \Pley\Entity\User\User $user, \Pley\Entity\Profile\ProfileSubscriptionPlan $subscriptionPlan)
    {
        /**
         * @var $paypalManager \Pley\Billing\PaypalManager
         */
        $paypalManager = app('\Pley\Billing\PaypalManager');
        $agreement = $paypalManager->getBillingAgreement($subscriptionPlan->getVPaymentSubscriptionId());
        $metadata = new \stdClass();

        $startTime = \DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $agreement->getStartDate());
        $nextBillingTime = \DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $agreement->getAgreementDetails()->getNextBillingDate());

        $metadata->current_period_start = $startTime->getTimestamp();
        $metadata->canceled_at = ($nextBillingTime) ? $nextBillingTime->getTimestamp() : null;
        $metadata->current_period_end = ($nextBillingTime) ? $nextBillingTime->getTimestamp() : null ;

        $paymentSubscription = new \Pley\Payment\Subscription($user);
        $paymentSubscription->initVendor(
            $this->_getVendorSystemId(), $agreement->getId(), $metadata
        );
        if($agreement->getState() === 'Active' || $agreement->getState() =='Suspended'){
            // Avoid cancellation attempt if agreement has been cancelled already
            $paypalManager->cancelBillingAgreement($subscriptionPlan->getVPaymentSubscriptionId());
        }

        return $paymentSubscription;
    }

    /**
     * Delegate method that resets a subscription back to have auto renew.
     * @param \Pley\Entity\User\User $user
     * @param \Pley\Entity\Profile\ProfileSubscriptionPlan $subscriptionPlan
     * @return \Pley\Payment\Subscription
     */
    protected function _reactivateAutoRenewDelegate(
        \Pley\Entity\User\User $user, \Pley\Entity\Profile\ProfileSubscriptionPlan $subscriptionPlan)
    {
        //TODO: implement this method
    }

    /**
     * Delegate method that adds a credit towards the User's Billing Account Balance.
     * @param \Pley\Entity\User\User $user
     * @param \Pley\Entity\Profile\ProfileSubscriptionPlan $profileSubscriptionPlan
     * @param float $amount
     * @param string $description
     */
    protected function _addCreditDelegate(\Pley\Entity\User\User $user, \Pley\Entity\Profile\ProfileSubscriptionPlan $profileSubscriptionPlan, $amount, $description = '')
    {
        /**
         * @var $paypalManager \Pley\Billing\PaypalManager
         */
        $paypalManager = app('\Pley\Billing\PaypalManager');
        $txns = $paypalManager->listBillingAgreementTransactions($profileSubscriptionPlan->getVPaymentSubscriptionId());

        foreach ($txns->getAgreementTransactionList() as $txn){
            if($txn->getStatus() === 'Completed' || $txn->getStatus() === 'Refunded'){
                $paypalManager->refundTransaction($txn->getTransactionId(), $amount, $description);
            }
        }
    }

    /**
     * Delegate method that gets a credit information towards the User's Billing Account Balance.
     * @param \Pley\Entity\User\User $user
     * @return \Pley\Payment\CreditData[]
     */
    protected function _getCreditInfoDelegate(\Pley\Entity\User\User $user)
    {
        //TODO: not possible with PayPal, add exception
    }
}