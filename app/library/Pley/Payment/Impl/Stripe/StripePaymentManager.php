<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Payment\Impl\Stripe;

/**
 * The <kbd>StripePaymentManager</kbd> is the specific vendor implementation of the 
 * <kbd>PaymentManagerInterface</kbd> for the STRIPE payments system.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Payment.Impl.Stripe
 * @subpackage Payment
 */
class StripePaymentManager extends \Pley\Payment\AbstractPaymentManager implements \Pley\Payment\PaymentManagerInterface
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
        return \Pley\Enum\PaymentSystemEnum::STRIPE;
    }
    
    /**
     * Creates a new User in the Vendor Payment system and returns the respective User object.
     * <p>It updates the internal reference of the user to the vendor payment system.</p>
     * @param \Pley\Entity\User\User $user
     * @return \Pley\Payment\PaymentAccount
     */
    protected function _createUserDelegate(\Pley\Entity\User\User $user)
    {   
        // Creating the Stripe User
        $stripeCustomerMap = \Stripe\Customer::create([
            'description' => '[Pleybox] ' . $user->getFirstName() . ' ' . $user->getLastName(),
            "email"       => $user->getEmail(),
            'metadata'    => [
                'userId'    => $user->getId(),
                'firstName' => $user->getFirstName(),
                'lastName'  => $user->getLastName(),
            ],
        ]);
        
        // Creating the Payment User object representation.
        $paymentUser = new \Pley\Payment\PaymentAccount($user);
        
        // Initializing the Vendor data into this object
        $vendorAccountId = $stripeCustomerMap->id;
        $paymentUser->initVendor($this->_getVendorSystemId(), $vendorAccountId, $stripeCustomerMap);
        
        return $paymentUser;
    }
    
    /** 
     * Delegate method that updates the email associated to the user account in the Vendor Payment system.
     * @param \Pley\Entity\User\User $user
     */
    protected function _updateUserEmailDelegate(\Pley\Entity\User\User $user)
    {
        $stripeCustomer = $this->_getStripeCustomer($user);
        $stripeCustomer->email = $user->getEmail();
        $stripeCustomer->save();
    }
    
    /**
     * Delegate method that checks whether the supplied card is valid.
     * @param \Pley\Entity\User\User          $user
     * @param \Pley\Payment\Method\CreditCard $card
     * @throws \Pley\Exception\Payment\PaymentMethodInvalidInputException If the card is invalid.
     */
    protected function _validateCardDelegate(\Pley\Entity\User\User $user, \Pley\Payment\Method\CreditCard $card)
    {
        $this->_getCardToken($user, $card);
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
        if ($paymentMethod instanceof \Pley\Entity\Payment\UserPaymentMethod) {
            $stripeCard = $this->_getStripeCard($user, $paymentMethod);
            
        } else { // $paymentMethod instanceof \Pley\Payment\Method\CreditCard
            $stripeToken = $this->_getCardToken($user, $paymentMethod);
            $stripeCard  = $stripeToken->card;
        }
        
        $creditCard = $this->_toCard($stripeCard);
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
        $creditCardList = $this->_getStripeCardList($user);
        
        // If the payment method list is set, turn it into a map so we can easily filter below
        // The key would be the Vendor Payment Method ID
        $paymentMethodMap = null;
        if (isset($paymentMethodList)) {
            $paymentMethodMap = [];
            foreach ($paymentMethodList as $paymentMethod) {
                $paymentMethodMap[$paymentMethod->getVPaymentMethodId()] = $paymentMethod;
            }
        }
        
        $filteredCreditCardList = [];
        foreach ($creditCardList as $stripeCard) {
            // If the map was created we need to filter, so ignore if the card ID is not in the map
            if (isset($paymentMethodMap) && !isset($paymentMethodMap[$stripeCard->id])) {
                continue;
            }
            
            // Otherwise, just add the parsed Card
            $filteredCreditCardList[] = $this->_toCard($stripeCard);
        }
        
        return $filteredCreditCardList;
    }
    
    /**
     * Delegate method that returns whether a credit card exists with the same number exists in the User's Account.
     * @param \Pley\Entity\User\User          $user
     * @param \Pley\Payment\Method\CreditCard $card
     * @return boolean <kbd>TRUE</kbd> if the user has this card already, <kbd>FALSE</kbd> otherwise.
     */
    protected function _isCardExistsDelegate(\Pley\Entity\User\User $user, \Pley\Payment\Method\CreditCard $card)
    {
        $stripeToken = $this->_getCardToken($user, $card);
        $isExists    = $this->_isCardExistByToken($user, $stripeToken);
        return $isExists;
    }
    
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
    protected function _getExistingCardDelegate(
            \Pley\Entity\User\User $user, $paymentMethodList, \Pley\Payment\Method\CreditCard $card)
    {
        $stripeToken = $this->_getCardToken($user, $card);
        
        // Mapping the user's payment methods by the vendor ID so we can easily find it when checking
        // the vendor payments registered.
        $paymentMethodMap = [];
        foreach ($paymentMethodList as $paymentMethod) {
            $paymentMethodMap[$paymentMethod->getVPaymentMethodId()] = $paymentMethod;
        }
        
        $userStripeCardList = $this->_getStripeCardList($user);
        $stripeCard         = $stripeToken->card;

        // Now try to locate the card given their fingerprints
        foreach ($userStripeCardList as $userStripeCard) {
            // If the fingerprints don't match, go to check the next entry
            if ($userStripeCard->fingerprint != $stripeCard->fingerprint) {
                continue;
            }
            
            // If there was a match, check against the MAP for the right PaymentMethod object reference
            if (isset($paymentMethodMap[$userStripeCard->id])) {
                return $paymentMethodMap[$userStripeCard->id];
            }

            // If the entry was not on the map, just finish the cycle so we can return NULL
            break;
        }
        
        // If we get to this point, it was not found.
        return null;
    }
    
    /**
     * Delegate method that adds a credit card to the User's Payment Account
     * @param \Pley\Entity\User\User          $user
     * @param \Pley\Payment\Method\CreditCard $card
     * @return \Pley\Payment\Method\CreditCard
     * @throws \Pley\Exception\Entity\ExistingPaymentMethodException if CreditCard already exists
     */
    protected function _addCardDelegate(\Pley\Entity\User\User $user, \Pley\Payment\Method\CreditCard $card)
    {
        // Verify if this card is already in the user's account
        $stripeToken = $this->_getCardToken($user, $card);
        if ($this->_isCardExistByToken($user, $stripeToken)) {
            throw new \Pley\Exception\Payment\PaymentMethodExistsException($user);
        }
        
        // Now that we know the card is good, we can go ahead and add it.
        try {
            $stripeCustomer = $this->_getStripeCustomer($user);
            $stripeCard     = $stripeCustomer->sources->create(['source' => $stripeToken->id]);
        } catch (\Stripe\Error\Card $cardError) {
            $this->_stripeErrorToException($cardError, ['user' => $user]);
        }

        $creditCard = $this->_toCard($stripeCard);
        return $creditCard;
    }
    
    /**
     * Delegate method that updates an existing's payment method expiration date.
     * @param \Pley\Entity\User\User $user
     * @param \Pley\Entity\Payment\UserPaymentMethod $paymentMethod
     * @param int                                    $expMonth
     * @param int                                    $expYear
     */
    protected function _updateCardDelegate(
            \Pley\Entity\User\User $user, \Pley\Entity\Payment\UserPaymentMethod $paymentMethod, $expMonth, $expYear)
    {
        $stripeCard = $this->_getStripeCard($user, $paymentMethod);
        
        $stripeCard->exp_month = $expMonth;
        $stripeCard->exp_year  = $expYear;
        
        try {
            $stripeCard->save();
        } catch (\Stripe\Error\Card $cardError) {
            $this->_stripeErrorToException($cardError, [
                'user'          => $user,
                'paymentMethod' => $paymentMethod,
            ]);
        }
    }
    
    /**
     * Delegate method that sets the supplied payment method as the Default for the given user.
     * @param \Pley\Entity\User\User                 $user
     * @param \Pley\Entity\Payment\UserPaymentMethod $paymentMethod
     */
    protected function _setDefaultCardDelegate(
            \Pley\Entity\User\User $user, \Pley\Entity\Payment\UserPaymentMethod $paymentMethod)
    {
        try {
            $stripeCustomer = $this->_getStripeCustomer($user);
            
            $stripeCustomer->default_source = $paymentMethod->getVPaymentMethodId();
            $stripeCustomer->save();
        } catch (\Stripe\Error\Card $cardError) {
            $this->_stripeErrorToException($cardError, [
                'user'          => $user,
                'paymentMethod' => $paymentMethod,
            ]);
        }
    }
    
    /**
     * Delegate method that makes a one-time single charge on the supplied card.
     * @param \Pley\Payment\Method\CreditCard $card
     * @param float                           $amount A positive value (> 0)
     * @param string                          $description   Detail string for this transaction.
     * @param array                           $metadata      (Optional)<br/>Key/Value paris to add 
     *      to the definition of this charge.
     * @return \Pley\Payment\Method\Transaction
     */
    protected function _singleChargeDelegate(
            \Pley\Payment\Method\CreditCard $card, $amount, $description, $metadata = null)
    {
        // Single charges don't require a user in our system, however, since the method to create
        // the token requires a User object, we just pass a dummy one
        $dummyUser   = \Pley\Entity\User\User::dummy();
        $stripeToken = $this->_getCardToken($dummyUser, $card);
        
        // Stripe receives amount in Cents, while we store it in dollars with cents as floating point
        $stripeAmount = $amount * 100;
        
        $stripeMetadata = $this->_getSanitizedMetadata([], $metadata);

        $stripeRequestData = [
            'amount'      => $stripeAmount,
            'currency'    => 'usd',
            'source'      => $stripeToken->id,
            'description' => $description,
            'metadata'    => $stripeMetadata,  
        ];
        
        try {
            $stripeCharge = \Stripe\Charge::create($stripeRequestData);
        } catch (\Stripe\Error\Card $cardError) {
            $this->_stripeErrorToException($cardError, ['user' => $dummyUser]);
        }
        
        $transaction = new \Pley\Payment\Method\Transaction(
            $amount, $stripeCharge->created
        );
        $transaction->initVendor($this->_getVendorSystemId(), $stripeCharge->id, $stripeCharge);
        
        return $transaction;
    }
    
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
    protected function _chargeDelegate(
            \Pley\Entity\User\User $user, \Pley\Entity\Payment\UserPaymentMethod $paymentMethod, 
            $amount, $description, $metadata = null)
    {
        $stripeCard = $this->_getStripeCard($user, $paymentMethod);
        
        // Stripe receives amount in Cents, while we store it in dollars with cents as floating point
        $stripeAmount = $amount * 100;
        
        $baseMetadata   = [
            'userId'              => $user->getId(),
            'userPaymentMethodId' => $paymentMethod->getId(),
        ];
        $stripeMetadata = $this->_getSanitizedMetadata($baseMetadata, $metadata);

        $stripeRequestData = [
            'customer'    => $user->getVPaymentAccountId(),
            'source'      => $stripeCard->id,
            'amount'      => $stripeAmount,
            'currency'    => 'usd',
            'description' => $description,
            'metadata'    => $stripeMetadata,
        ];
        
        try {
            $stripeCharge = \Stripe\Charge::create($stripeRequestData);
        } catch (\Stripe\Error\Card $cardError) {
            $this->_stripeErrorToException($cardError, [
                'user'          => $user,
                'paymentMethod' => $paymentMethod,
            ]);
        }
        
        $transaction = new \Pley\Payment\Method\Transaction(
            $amount, $stripeCharge->created
        );
        $transaction->initVendor($this->_getVendorSystemId(), $stripeCharge->id, $stripeCharge);
        
        return $transaction;
    }
    
    /**
     * Delegate method that returns the Subscription information from the vendor system.
     * @param \Pley\Entity\User\User                       $user
     * @param \Pley\Entity\Profile\ProfileSubscriptionPlan $subscriptionPlan
     * @return \Pley\Payment\Subscription
     */
    protected function _getSubscriptionDelegate(
            \Pley\Entity\User\User $user, \Pley\Entity\Profile\ProfileSubscriptionPlan $subscriptionPlan)
    {
        $stripeSubscription = \Stripe\Subscription::retrieve($subscriptionPlan->getVPaymentSubscriptionId());
        
        $paymentSubscription = new \Pley\Payment\Subscription($user);
        $paymentSubscription->initVendor(
            $this->_getVendorSystemId(), $stripeSubscription->id, $stripeSubscription
        );
        
        return $paymentSubscription;
    }
    
    /**
     * Delegate method that creates a new subscription to begin on a future day on a given PaymentPlan.
     * @param \Pley\Entity\User\User                 $user
     * @param \Pley\Entity\Payment\VendorPaymentPlan $vPaymentPlan
     * @param int                                    $beginDate    (Optional)
     * @param array                                  $metadata     (Optional)<br/>Key/Value paris 
     * @return \Pley\Payment\Subscription
     */
    protected function _addSubscriptionDelegate(\Pley\Entity\User\User $user, \Pley\Entity\Payment\VendorPaymentPlan $vPaymentPlan, 
            $beginDate = null, $metadata = null)
    {
        $baseMetadata   = ['userId' => $user->getId()];
        $stripeMetadata = $this->_getSanitizedMetadata($baseMetadata, $metadata);
        
        $subscriptionDetailMap = [
            'customer' => $user->getVPaymentAccountId(),
            'plan'     => $vPaymentPlan->getVPaymentPlanId(),
            'metadata' => $stripeMetadata,
        ];
        
        if (isset($beginDate)) {
            $subscriptionDetailMap['trial_end'] = $beginDate;
        }

        try {
            $stripeSubscription = \Stripe\Subscription::create($subscriptionDetailMap);
        } catch (\Stripe\Error\Card $cardError) {
            $this->_stripeErrorToException($cardError, ['user' => $user]);
        }
        
        $paymentSubscription = new \Pley\Payment\Subscription($user);
        $paymentSubscription->initVendor(
            $this->_getVendorSystemId(), $stripeSubscription->id, $stripeSubscription
        );
        
        return $paymentSubscription;
    }
    
    /**
     * Delegate method that stops the subscription from auto-renewing at the end of the billing period.
     * @param \Pley\Entity\User\User                       $user
     * @param \Pley\Entity\Profile\ProfileSubscriptionPlan $subscriptionPlan
     * @return \Pley\Payment\Subscription
     */
    protected function _stopSubscriptionAutoRenewDelegate(
            \Pley\Entity\User\User $user, \Pley\Entity\Profile\ProfileSubscriptionPlan $subscriptionPlan)
    {
        $atPeriodEnd         = true;
        $paymentSubscription = $this->_subscriptionCancel($user, $subscriptionPlan, $atPeriodEnd);
        return $paymentSubscription;
    }

    /**
     * Delegate method that pauses subscription for a certain amount of time
     * @param \Pley\Entity\User\User                       $user
     * @param \Pley\Entity\Profile\ProfileSubscriptionPlan $subscriptionPlan
     * @param int $pauseDateEnd
     * @return \Pley\Payment\Subscription
     * @throws \Exception
     */

    protected function _pauseSubscriptionDelegate(
        \Pley\Entity\User\User $user, \Pley\Entity\Profile\ProfileSubscriptionPlan $subscriptionPlan, $pauseDateEnd){

        try {
            $stripeCustomer     = $this->_getStripeCustomer($user);
            $stripeSubscription = \Stripe\Subscription::retrieve($subscriptionPlan->getVPaymentSubscriptionId());

            // Unlike other Stripe calls where we retrieve the customer and then from the customer
            // object we retrieve other info, subscriptions are retrieved directly, so we are adding
            // an extra check to make sure that the data supplied matches
            if ($stripeCustomer->id != $stripeSubscription->customer) {
                throw new \Exception("The Subscription supplied does not belong to the User.");
            }
            /**
             * adding a trial period in the middle of an existing subscription
             * to simulate pause and shift subscription one period forward
             * See https://stripe.com/docs/subscriptions/billing-cycle#api
             */
            $stripeSubscription->trial_end = $pauseDateEnd;
            $stripeSubscription->prorate = false;
            $stripeSubscription->save();

        } catch (\Stripe\Error\Card $cardError) {
            $this->_stripeErrorToException($cardError, ['user' => $user]);
        }

        $paymentSubscription = new \Pley\Payment\Subscription($user);
        $paymentSubscription->initVendor(
            $this->_getVendorSystemId(), $stripeSubscription->id, $stripeSubscription
        );
        return $paymentSubscription;
    }

    /**
     * Delegate method that stops the subscription from auto-renewing and flags it as cancelled.
     * @param \Pley\Entity\User\User                       $user
     * @param \Pley\Entity\Profile\ProfileSubscriptionPlan $subscriptionPlan
     * @return \Pley\Payment\Subscription
     */
    protected function _subscriptionCancelDelegate(
            \Pley\Entity\User\User $user, \Pley\Entity\Profile\ProfileSubscriptionPlan $subscriptionPlan)
    {
        $paymentSubscription = $this->_subscriptionCancel($user, $subscriptionPlan);
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
        try {
            $stripeCustomer     = $this->_getStripeCustomer($user);
            $stripeSubscription = \Stripe\Subscription::retrieve($subscriptionPlan->getVPaymentSubscriptionId());
            
            // Unlike other Stripe calls where we retrieve the customer and then from the customer
            // object we retrieve other info, subscriptions are retrieved directly, so we are adding
            // an extra check to make sure that the data supplied matches
            if ($stripeCustomer->id != $stripeSubscription->customer) {
                throw new \Exception("The Subscription supplied does not belong to the User.");
            }
            
            // To remove any AutoRenew Stop (cancel-at-period-end), Docs indicate to update the 
            // subscription plan to itself.
            // https://stripe.com/docs/subscriptions/canceling-pausing#reactivating-canceled-subscriptions
            $stripeSubscription->plan = $subscriptionPlan->getVPaymentPlanId();
            $stripeSubscription->save();
            
        } catch (\Stripe\Error\Card $cardError) {
            $this->_stripeErrorToException($cardError, ['user' => $user]);
        }
        
        $paymentSubscription = new \Pley\Payment\Subscription($user);
        $paymentSubscription->initVendor(
            $this->_getVendorSystemId(), $stripeSubscription->id, $stripeSubscription
        );
        
        return $paymentSubscription;
    }
    
    /**
     * Delegate method that adds a credit towards the User's Billing Account Balance.
     * @param \Pley\Entity\User\User $user
     * @param \Pley\Entity\Profile\ProfileSubscriptionPlan $profileSubscriptionPlan
     * @param float                  $amount
     * @param string                 $description
     */
    protected function _addCreditDelegate(\Pley\Entity\User\User $user, \Pley\Entity\Profile\ProfileSubscriptionPlan $profileSubscriptionPlan, $amount, $description = '')
    {
        try {
            $stripeCustomer = $this->_getStripeCustomer($user);
            
            // A credit in Stripe is handled as a Negative invoice.
            $stripeCreditAmount = -$amount * 100;
            
            $invoiceItem = \Stripe\InvoiceItem::create([
                'customer'    => $stripeCustomer,
                'amount'      => $stripeCreditAmount,
                'currency'    => 'usd',
                'description' => $description,
            ]);
        } catch (\Stripe\Error\Card $cardError) {
            $this->_stripeErrorToException($cardError, ['user' => $user]);
        }
    }

    /**
     * Delegate method that gets a credit information towards the User's Billing Account Balance.
     * @param \Pley\Entity\User\User $user
     * @return \Pley\Payment\CreditData[]
     */
    protected function _getCreditInfoDelegate(\Pley\Entity\User\User $user)
    {
        try {
            $stripeCustomer = $this->_getStripeCustomer($user);
            $invoiceItems = \Stripe\InvoiceItem::all([
                'customer' => $stripeCustomer,
            ]);
            $creditsCollection = [];

            if (count($invoiceItems->data)) {
                foreach ($invoiceItems->data as $invoiceItem) {
                    if ($invoiceItem->amount >= 0) {
                        continue; //only items with negative amount are actual credit
                    }
                    $creditData = new \Pley\Payment\CreditData();
                    $creditData->setAmount(number_format($invoiceItem->amount / 100, 2));
                    $creditData->setDescription($invoiceItem->description);
                    $creditData->setCreatedAt($invoiceItem->date);
                    $creditsCollection[] = $creditData;
                }
            }
            return $creditsCollection;

        } catch (\Stripe\Error\Card $cardError) {
            $this->_stripeErrorToException($cardError, ['user' => $user]);
        }
    }
    
    // ---------------------------------------------------------------------------------------------
    // SPECIAL METHOD TO DELETE A CARD, NOT JUST HIDE IT, IT IS NOT VISIBLE AS IT NOT INTENDED FOR
    // ANY USE OTHER THAN WHEN ADDING THE VERY FIRST CARD FOR THE VERY FIRST PAID SUBSCRIPTION FAILS
    protected function _deleteFirstCardDelegate(
            \Pley\Entity\User\User $user, \Pley\Entity\Payment\UserPaymentMethod $paymentMethod)
    {
        $stripeCard = $this->_getStripeCard($user, $paymentMethod);
        
        try {
            $stripeCard->delete();
        } catch (\Stripe\Error\Card $cardError) {
            $this->_stripeErrorToException($cardError, [
                'user'          => $user,
                'paymentMethod' => $paymentMethod,
            ]);
        }
    }
    
    // ---------------------------------------------------------------------------------------------
    // Private Methods to STRIPE implementation ----------------------------------------------------
    // ---------------------------------------------------------------------------------------------
    
    /** @return \Stripe\Customer */
    private function _getStripeCustomer(\Pley\Entity\User\User $user)
    {
        if (!isset($this->_cache[$user->getId()])) {
            $this->_cache[$user->getId()] = \Stripe\Customer::retrieve($user->getVPaymentAccountId());
        }
        
        return $this->_cache[$user->getId()];
    }
    
    /**
     * Returns the Stripe credit card requested for the given user
     * @param \Pley\Entity\User\User                 $user
     * @param \Pley\Entity\Payment\UserPaymentMethod $paymentMethod
     * @return \Stripe\Card
     * @throws \Pley\Exception\Payment\PaymentMethodUnexistentException
     */
    private function _getStripeCard(
            \Pley\Entity\User\User $user, \Pley\Entity\Payment\UserPaymentMethod $paymentMethod)
    {
        try {
            $stripeCustomer = $this->_getStripeCustomer($user);
            $stripeCard     = $stripeCustomer->sources->retrieve($paymentMethod->getVPaymentMethodId());
        } catch (\Stripe\Error\Card $cardError) {
            $this->_stripeErrorToException($cardError, [
                'user'          => $user,
                'paymentMethod' => $paymentMethod,
            ]);
        }
        
        if (empty($stripeCard)) {
            throw new \Pley\Exception\Payment\PaymentMethodUnexistentException($user, $paymentMethod);
        }
        
        return $stripeCard;
    }
    
    /**
     * Returns a list of all the Stripe credit cards stored for the given user.
     * @param \Pley\Entity\User\User $user
     * @return \Stripe\Card[]
     */
    private function _getStripeCardList(\Pley\Entity\User\User $user)
    {
        try {
            $stripeCustomer = $this->_getStripeCustomer($user);
            $cardCollection = $stripeCustomer->sources->all(['object' => 'card']);
        } catch (\Stripe\Error\Card $cardError) {
            $this->_stripeErrorToException($cardError, ['user' => $user]);
        }
        
        $creditCardList = $cardCollection->data;
        return $creditCardList;
    }
    
    /**
     * Retrieves the Stripe Token for the card details supplied.
     * <p>This is used mainly to check if a card exists by it's fingerprint or to add it once checked.</p>
     * 
     * @param \Pley\Entity\User\User          $user
     * @param \Pley\Payment\Method\CreditCard $card
     * @return \Stripe\Token
     */
    private function _getCardToken(\Pley\Entity\User\User $user, \Pley\Payment\Method\CreditCard $card)
    {
        $cardDetailMap = [
            'number'    => $card->getNumber(),
            'exp_month' => $card->getExpirationMonth(),
            'exp_year'  => $card->getExpirationYear(),
        ];
        
        if (!empty($card->getCVV())) {
            $cardDetailMap['cvc'] = $card->getCVV();
        }
        
        if (!empty($card->getBillingAddress())) {
            $billingAddress = $card->getBillingAddress();
            $cardDetailMap['address_line1']   = $billingAddress->getStreet1();
            $cardDetailMap['address_line2']   = $billingAddress->getStreet2();
            $cardDetailMap['address_city']    = $billingAddress->getCity();
            $cardDetailMap['address_state']   = $billingAddress->getState();
            $cardDetailMap['address_zip']     = $billingAddress->getZipCode();
            $cardDetailMap['address_country'] = $billingAddress->getCountry();
        }
        
        try {
            $stripeToken = \Stripe\Token::create(['card' => $cardDetailMap]);
        } catch (\Stripe\Error\Card $cardError) {
            $this->_stripeErrorToException($cardError, ['user' => $user]);
        }
        
        return $stripeToken;
    }
    
    /**
     * Returns whether a Card represented by a StripeToken exists in the User's Card list.
     * @param \Pley\Entity\User\User $user
     * @param \Stripe\Token $stripeToken
     * @return boolean
     */
    private function _isCardExistByToken(\Pley\Entity\User\User $user, \Stripe\Token $stripeToken)
    {
        $userStripeCardList = $this->_getStripeCardList($user);
        $stripeCard         = $stripeToken->card;

        $isExists = false;
        foreach ($userStripeCardList as $userStripeCard) {
            if ($userStripeCard->fingerprint == $stripeCard->fingerprint) {
                $isExists = true;
                break;
            }
        }
        
        return $isExists;
    }
    
    /**
     * Merges two metadata objects.
     * @param array $baseMetadata     The source data that is required for the action.
     * @param array $suppliedMetadata (Optional)<br/>Additional data to pass along with the action.
     * @return array
     */
    private function _getSanitizedMetadata($baseMetadata, $suppliedMetadata = null)
    {
        if (!isset($suppliedMetadata)) {
            return $baseMetadata;
        }
        
        // Base metadata comes as the second parameter as it needs to override with specific values
        // of the transaction, any of those that the outer might've passed
        return array_merge($suppliedMetadata, $baseMetadata);
    }
    
    /**
     * Mapper between Stripe's payment method brand and our internal IDs
     * @param string $brandValue
     * @return int A value from <kbd>\Pley\Enum\PaymentMethodBrandEnum</kbd>
     */
    private function _getMethodBrandId($brandValue)
    {
        switch ($brandValue) {
            case 'Visa'            : return \Pley\Enum\PaymentMethodBrandEnum::VISA;
            case 'MasterCard'      : return \Pley\Enum\PaymentMethodBrandEnum::MASTER_CARD;
            case 'American Express': return \Pley\Enum\PaymentMethodBrandEnum::AMERICAN_EXPRESS;
            case 'JCB'             : return \Pley\Enum\PaymentMethodBrandEnum::JCB;
            case 'Discover'        : return \Pley\Enum\PaymentMethodBrandEnum::DISCOVER;
            case 'Diners Club'     : return \Pley\Enum\PaymentMethodBrandEnum::DINERS_CLUB;
            default:
                throw new \Exception('Unknown Payment Method Brand');
        }
    }
    
    /**
     * Converts a Stripe card into our internal object representation.
     * @param \Stripe\Card $stripeCard
     * @return \Pley\Payment\Method\CreditCard
     */
    private function _toCard(\Stripe\Card $stripeCard)
    {
        $cardId = $stripeCard->id;
        
        $creditCard = new \Pley\Payment\Method\CreditCard(
            $stripeCard->last4, $stripeCard->exp_month, $stripeCard->exp_year
        );
        $creditCard->setBrand($this->_getMethodBrandId($stripeCard->brand));
        $creditCard->setType($stripeCard->funding);
        $creditCard->initVendor($this->_getVendorSystemId(), $cardId, $stripeCard);
        
        // If the address is set, then we can fill also the billing address
        if (isset($stripeCard->address_line1)) {
            $billingAddress = new \Pley\Payment\Method\BillingAddress(
                $stripeCard->address_line1, 
                $stripeCard->address_line2, 
                $stripeCard->address_city, 
                $stripeCard->address_state, 
                $stripeCard->address_zip, 
                $stripeCard->address_country
            );
            $creditCard->setBillingAddress($billingAddress);
        }
        
        return $creditCard;
    }
    
    /**
     * Common method for cancelling a subscription whether final or at the end of the billing period.
     * @param \Pley\Entity\User\User                       $user
     * @param \Pley\Entity\Profile\ProfileSubscriptionPlan $subscriptionPlan
     * @param boolean                                      $atPeriodEnd      (Optional)<br/>Default <kbd>false</kbd>
     * @return \Pley\Payment\Subscription
     */
    private function _subscriptionCancel(
            \Pley\Entity\User\User $user, \Pley\Entity\Profile\ProfileSubscriptionPlan $subscriptionPlan, $atPeriodEnd = false)
    {
        try {
            $stripeCustomer     = $this->_getStripeCustomer($user);
            $stripeSubscription = \Stripe\Subscription::retrieve($subscriptionPlan->getVPaymentSubscriptionId());
            
            // Unlike other Stripe calls where we retrieve the customer and then from the customer
            // object we retrieve other info, subscriptions are retrieved directly, so we are adding
            // an extra check to make sure that the data supplied matches
            if ($stripeCustomer->id != $stripeSubscription->customer) {
                throw new \Exception("The Subscription supplied does not belong to the User.");
            }
            
            $params = null;
            if ($atPeriodEnd) {
                $params = ['at_period_end' => true];
            }
            
            $stripeSubscription->cancel($params);
        } catch (\Stripe\Error\Card $cardError) {
            $this->_stripeErrorToException($cardError, ['user' => $user]);
        }
        
        $paymentSubscription = new \Pley\Payment\Subscription($user);
        $paymentSubscription->initVendor(
            $this->_getVendorSystemId(), $stripeSubscription->id, $stripeSubscription
        );
        
        return $paymentSubscription;
    }
    
    /**
     * Takes a Stripe Card Error and converts into our internal Exception representation.
     * @param \Stripe\Error\Card $cardError
     * @param array              $metadata  Array to store extra info needed to be passed to exceptions.
     * @throws \Pley\Http\Response\ExceptionInterface The mapped exception.
     * @throws \Stripe\Error\Card                     The source exception if it couldn't be mapped.
     * @see https://stripe.com/docs/api#errors
     */
    private function _stripeErrorToException(\Stripe\Error\Card $cardError, $metadata = null)
    {
        $stripeErrCode = $cardError->getStripeCode();
        $user          = isset($metadata['user'])? $metadata['user'] : null;
        $paymentMethod = isset($metadata['paymentMethod'])? $metadata['paymentMethod'] : null;
        
        switch ($stripeErrCode) {
            case 'invalid_expiry_month' : // The card's expiration month is invalid.
            case 'invalid_expiry_year' :  // The card's expiration year is invalid.
            case 'expired_card' :         // The card has expired.
            case 'invalid_number' :       // The card number is not a valid credit card number.
                throw new \Pley\Exception\Payment\PaymentMethodExpiredException($user, $paymentMethod, $cardError);
            case 'invalid_cvc' :          // The card's security code is invalid.
            case 'invalid_swipe_data' :   // The card's swipe data is invalid.
            case 'incorrect_number' :     // The card number is incorrect.
            case 'incorrect_cvc' :        // The card's security code is incorrect.
                throw new \Pley\Exception\Payment\PaymentMethodInvalidInputException($user, $paymentMethod, $cardError);
            case 'incorrect_zip' :        // The card's zip code failed validation.
                throw new \Pley\Exception\Payment\PaymentMethodZipException($user, $cardError);
            case 'card_declined' :        // The card was declined.
                throw new \Pley\Exception\Payment\PaymentMethodDeclinedException($user, $paymentMethod, $cardError);
            case 'missing' :              // There is no card on a customer that is being charged.
                throw new \Pley\Exception\Payment\PaymentMethodUnexistentException($user, $paymentMethod, $cardError);
            case 'processing_error' :     // An error occurred while processing the card.
                throw new \Pley\Exception\Payment\PaymentSystemProcessingException($cardError);
        }
        
        // If we couldn't map the exception, then just propagate what was triggered
        throw $cardError;
    }
}
