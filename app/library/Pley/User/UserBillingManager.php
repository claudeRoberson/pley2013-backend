<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\User;

/**
 * The <kbd>UserBillingManager</kbd> class handles events related to billing between our storage
 * and the vendor payment system via the <kbd>PaymentManager</kbd> library.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.User
 * @subpackage User
 */
class UserBillingManager
{
    /** @var int */
    private static $DEFAULT_PAYMENT_SYSTEM_ID;
    
    /** @var \Pley\Db\AbstractDatabaseManager */
    protected $_dbManager;
    /** @var \Pley\Dao\User\UserDao */
    protected $_userDao;
    /** @var \Pley\Dao\Payment\UserPaymentMethodDao */
    protected $_userPaymentMethodDao;
    /** @var \Pley\Dao\Gift\GiftDao */
    protected $_giftDao;
    /** @var \Pley\Payment\PaymentManagerFactory */
    protected $_paymentManagerFactory;
    
    public function __construct(
            \Pley\Db\AbstractDatabaseManager $dbManager,
            \Pley\Dao\User\UserDao $userDao,
            \Pley\Dao\Payment\UserPaymentMethodDao $userPaymentMethodDao,
            \Pley\Dao\Gift\GiftDao $giftDao,
            \Pley\Payment\PaymentManagerFactory $paymentManagerFactory)
    {
        $this->_dbManager = $dbManager;
        
        $this->_userDao              = $userDao;
        $this->_userPaymentMethodDao = $userPaymentMethodDao;
        $this->_giftDao              = $giftDao;

        $this->_paymentManagerFactory = $paymentManagerFactory;

        static::$DEFAULT_PAYMENT_SYSTEM_ID = \Pley\Enum\PaymentSystemEnum::STRIPE;
    }

    /**
     * Creates the Vendor Payment Account for the supplied User and updates the User in the storage.
     * @param \Pley\Entity\User\User $user
     */
    public function addUserAccount(\Pley\Entity\User\User $user)
    {
        $paymentManager = $this->_paymentManagerFactory->getManager(static::$DEFAULT_PAYMENT_SYSTEM_ID);
        
        $that = $this;
        $this->_dbManager->transaction(function() use ($that, $paymentManager, $user) {
            $that->_addUserAccountClosure($paymentManager, $user);
        });
    }
    
    /**
     * Checks whether the supplied card is valid.
     * <p>If the user is new and hasn't been assigned an ID yet, the default payment system will be
     * used for validation, otherwise the one assigned to the user will be used instead.</p>
     * 
     * @param \Pley\Entity\User\User          $user
     * @param \Pley\Payment\Method\CreditCard $card
     * @throws \Pley\Exception\Payment\PaymentMethodInvalidInputException If the card is invalid.
     */
    public function valdiateCard(\Pley\Entity\User\User $user, \Pley\Payment\Method\CreditCard $card)
    {
        $paymentSystemId = static::$DEFAULT_PAYMENT_SYSTEM_ID;
        if (!empty($user->getVPaymentSystemId())) {
            $paymentSystemId = $user->getVPaymentSystemId();
        }
        
        // The billing address is required to add any credit card
        if (empty($card->getBillingAddress())) {
            throw new \Pley\Exception\Payment\PaymentMethodInvalidInputException($user);
        }
            
        $paymentManager = $this->_paymentManagerFactory->getManager($paymentSystemId);
        $paymentManager->validateCard($user, $card);
    }
    
    /**
     * Adds a card into the system for the supplied user.
     * @param \Pley\Entity\User\User          $user
     * @param \Pley\Payment\Method\CreditCard $card
     * @return \Pley\Entity\Payment\UserPaymentMethod
     * @throws \Pley\Exception\Payment\PaymentMethodExistsException
     */
    public function addCard(\Pley\Entity\User\User $user, \Pley\Payment\Method\CreditCard $card)
    {
        if (empty($user->getId()) || empty($user->getVPaymentSystemId())) {
            throw new \InvalidArgumentException("User doesn't exist or is not set with a vendor account");
        }
        
        $paymentManager = $this->_paymentManagerFactory->getManager($user->getVPaymentSystemId());
        $isDbEntryOnly    = false;
        
        // Validating that that the card doesn't exist yet for the user, however, if it does, there
        // are three actions to take
        //   A) If the it is stored and visible, throw an exception for duplicate card
        //   B) If the card is stored but NOT visible (aka, deleted), make it visible and update it
        //   C) If the card is NOT stored, the card somehow was added on the Vendor system but not
        //      recorded in ours, so just add the DB entry.
        if ($paymentManager->isCardExists($user, $card)) {
            // Retrieving the existing User's Card List to match it against the supplied Raw card
            $paymentMethodList = $this->_userPaymentMethodDao->findByUser(
                $user->getId(), \Pley\Dao\Payment\UserPaymentMethodDao::INCLUDE_HIDDEN
            );
            
            $existingPaymentMethod = $paymentManager->getExistingCard($user, $paymentMethodList, $card);
            
            // This is case `A`
            if (!empty($existingPaymentMethod) && $existingPaymentMethod->isVisible()) {
                throw new \Pley\Exception\Payment\PaymentMethodExistsException($user);
            }
                
            // This is case `B`
            if (!empty($existingPaymentMethod)) {
                $existingPaymentMethod->setIsVisible(true);
                $expMonth = $card->getExpirationMonth(); 
                $expYear  = $card->getExpirationYear();
                $this->updateCard($user, $existingPaymentMethod, $expMonth, $expYear);
                return $existingPaymentMethod;
                
            // This is case `C`
            } else {
                $isDbEntryOnly = true;
            }
        }
        
        // Now add the card into the system
        $that = $this;
        $paymentMethod = $this->_dbManager->transaction(
            function() use ($that, $paymentManager, $user, $card, $isDbEntryOnly) {
                return $that->_addCardClosure($paymentManager, $user, $card, $isDbEntryOnly);
            }
        );
        
        return $paymentMethod;
    }
    
    /**
     * Updates the Expiration of a user's supplied Payment Method.
     * @param \Pley\Entity\User\User                 $user
     * @param \Pley\Entity\Payment\UserPaymentMethod $existingPaymentMethod
     * @param int                                    $expMonth
     * @param int                                    $expYear
     */
    public function updateCard(
            \Pley\Entity\User\User $user, \Pley\Entity\Payment\UserPaymentMethod $existingPaymentMethod, 
            $expMonth, $expYear)
    {
        $this->_validateModificationRequest($user, $existingPaymentMethod);
        
        $paymentManager = $this->_paymentManagerFactory->getManager($user->getVPaymentSystemId());
        
        // Now add the card into the system
        $that = $this;
        $this->_dbManager->transaction(
            function() use ($that, $paymentManager, $user, $existingPaymentMethod, $expMonth, $expYear) {
                $that->_updateCardClosure($paymentManager, $user, $existingPaymentMethod, $expMonth, $expYear);
            }
        );
    }
    
    /**
     * Sets the supplied payment method as the default for the user.
     * @param \Pley\Entity\User\User                 $user
     * @param \Pley\Entity\Payment\UserPaymentMethod $paymentMethod
     */
    public function setDefaultCard(
            \Pley\Entity\User\User $user, \Pley\Entity\Payment\UserPaymentMethod $paymentMethod)
    {
        $this->_validateModificationRequest($user, $paymentMethod);
        
        $paymentManager = $this->_paymentManagerFactory->getManager($user->getVPaymentSystemId());
        
        // Now add the card into the system
        $that = $this;
        $this->_dbManager->transaction(
            function() use ($that, $paymentManager, $user, $paymentMethod) {
                $that->_setDefaultCardClosure($paymentManager, $user, $paymentMethod);
            }
        );
    }
    
    /**
     * Removes a card from the user's list 
     * <p>It actually just hides it for the user as we keep it for reference of the transactions done
     * by the user so Customer Service can have more info when a user questions, it allso allows us
     * to re-enable a card if a user adds the same card again after deleting it, without losing history
     * </p>
     * @param \Pley\Entity\User\User                 $user
     * @param \Pley\Entity\Payment\UserPaymentMethod $paymentMethod
     * @throws \Pley\Exception\User\PaymentMethodDeleteException If trying to delete the default payment
     */
    public function removeCard(
            \Pley\Entity\User\User $user, \Pley\Entity\Payment\UserPaymentMethod $paymentMethod)
    {
        $this->_validateModificationRequest($user, $paymentMethod);
        
        // Making sure that it is not an attempt to disable the default payment method
        if ($user->getDefaultPaymentMethodId() == $paymentMethod->getId()) {
            throw new \Pley\Exception\User\PaymentMethodDeleteException($user, $paymentMethod);
        }
        
        // A payment method is never really deleted, it is only hidden for the user, since we need
        // to keep this references for the sake of transaction history.
        // So there is no need to go to the Vendor to actually delete the card.
        $paymentMethod->setIsVisible(false);
        $this->_userPaymentMethodDao->save($paymentMethod);
    }
    
    /**
     * Processes a payment to purchase the supplied gift.
     * @param \Pley\Entity\Gift\Gift                 $gift
     * @param \Pley\Entity\Subscription\Subscription $subscription
     * @param \Pley\Entity\Gift\GiftPrice            $giftPrice
     * @param \Pley\Payment\Method\CreditCard        $card
     * @return string The Purchase Gift Token
     */
    public function purchaseGift(
            \Pley\Entity\Gift\Gift $gift, 
            \Pley\Entity\Subscription\Subscription $subscription,
            \Pley\Entity\Gift\GiftPrice $giftPrice, 
            \Pley\Payment\Method\CreditCard $card)
    {
        if (!empty($gift->getId())) {
            new \Pley\Exception\Entity\EntityExistsException(\Pley\Entity\Gift\Gift::class, $gift->getId());
        }
        
        // This should always pass unless the code that called this method didn't preform the right
        // validations or mistakenly is sending unrelated objects.
        if ($gift->getGiftPriceId() != $giftPrice->getId()) {
            throw new \Exception('Invalid relationship between Gift and GiftPrice');
        }
        
        $paymentManager = $this->_paymentManagerFactory->getManager(static::$DEFAULT_PAYMENT_SYSTEM_ID);
        
        $that  = $this;
        $token = $this->_dbManager->transaction(
            function() use ($that, $paymentManager, $gift, $subscription, $giftPrice, $card) {
                return $that->_purchaseGiftClosure(
                    $paymentManager, $gift, $subscription, $giftPrice, $card
                );
            }
        );
        
        return $token;
    }
    
    // ---------------------------------------------------------------------------------------------
    // SPECIAL METHOD TO DELETE A CARD, NOT JUST HIDE IT, IT IS NOT VISIBLE AS IT NOT INTENDED FOR
    // ANY USE OTHER THAN WHEN ADDING THE VERY FIRST CARD FOR THE VERY FIRST PAID SUBSCRIPTION FAILS
    protected function _deleteFirstCard(
            \Pley\Entity\User\User $user, \Pley\Entity\Payment\UserPaymentMethod $paymentMethod)
    {
        $this->_dbManager->checkActiveTransaction(__METHOD__);
        
        $this->_validateModificationRequest($user, $paymentMethod);
        
        // Just like this call, the call to delete the card from the Vendor is also hidden for the exact
        // same reasons.
        $paymentManager   = $this->_paymentManagerFactory->getManager($user->getVPaymentSystemId());
        $refClassPayment  = new \ReflectionClass(get_class($paymentManager));
        $refMethodPayment = $refClassPayment->getMethod('_deleteFirstCard');
        $refMethodPayment->setAccessible(true);
        $refMethodPayment->invoke($paymentManager, $user, $paymentMethod);
        
        // And similar to this, is the call to delete the card from the DB
        // Note: Though this should technically not be needed because it is executed within a transaction
        // and the DB record removed as part of the rollback, we just want to ensure that such is the case.
        $refClassDao  = new \ReflectionClass(get_class($this->_userPaymentMethodDao));
        $refMethodDao = $refClassDao->getMethod('_delete');
        $refMethodDao->setAccessible(true);
        $refMethodDao->invoke($this->_userPaymentMethodDao, $paymentMethod);
    }
    
    // ---------------------------------------------------------------------------------------------
    // CLOSURE METHODS FOR OPERATIONS WITHIN A DB TRANSACTION --------------------------------------
    
    /**
     * Checks that the supplied parameters of the request are valid and related, otherwise exceptions
     * are thrown.
     * @param \Pley\Entity\User\User $user
     * @param \Pley\Entity\Payment\UserPaymentMethod $paymentMethod
     */
    private function _validateModificationRequest(\Pley\Entity\User\User $user, \Pley\Entity\Payment\UserPaymentMethod $paymentMethod)
    {
        if (empty($user->getId()) || empty($user->getVPaymentSystemId())) {
            throw new \InvalidArgumentException("User doesn't exist or is not set with a vendor account");
        }
        if (empty($paymentMethod->getId())) {
            throw new \InvalidArgumentException("Payment Method doesn't exist");
        }
        if ($paymentMethod->getUserId() != $user->getId()) {
            throw new \Exception('Mismatching relationship');
        }
    }
    
    /**
     * Closure method to perform the creation of the vendor payment account and update of the user
     * in the storage within a transaction.
     * @param \Pley\Payment\PaymentManagerInterface $paymentManager
     * @param \Pley\Entity\User\User                $user
     */
    private function _addUserAccountClosure(
            \Pley\Payment\PaymentManagerInterface $paymentManager, \Pley\Entity\User\User $user)
    {
        $this->_dbManager->checkActiveTransaction(__METHOD__);
        
        // This method internally updates the VendorSystemID and VendorAccountId on `$user`
        $paymentManager->createUser($user);
        $this->_userDao->save($user);
    }
    
    /**
     * Closure method to add a new card on the vendor payment account and into our storage in a transaction.
     * @param \Pley\Payment\PaymentManagerInterface $paymentManager
     * @param \Pley\Entity\User\User                $user
     * @param \Pley\Payment\Method\CreditCard       $card
     * @param boolean $isDbEntryOnly
     * @return \Pley\Entity\Payment\UserPaymentMethod
     */
    private function _addCardClosure(
            \Pley\Payment\PaymentManagerInterface $paymentManager, 
            \Pley\Entity\User\User $user, 
            \Pley\Payment\Method\CreditCard $card, 
            $isDbEntryOnly)
    {
        $this->_dbManager->checkActiveTransaction(__METHOD__);
        
        // If $dbEntryOnly was TRUE, it means that this card exists on the vendor but somehow not
        // in our system, so we just retrieve such data instead of adding a new card.
        if ($isDbEntryOnly) {
            $addedCard = $paymentManager->getCard($user, $card);
            
        } else {
            $addedCard =  $paymentManager->addCard($user, $card);
        }
        
        $paymentMethod = \Pley\Entity\Payment\UserPaymentMethod::withNew(
            $user->getId(), $addedCard->getVendorSystemId(), $addedCard->getVendorId()
        );
        $this->_userPaymentMethodDao->save($paymentMethod);
        
        return $paymentMethod;
    }
    
    /**
     * Closure method to update the Expiration of a user's supplied Payment Method as a transaction.
     * @param \Pley\Payment\PaymentManagerInterface  $paymentManager
     * @param \Pley\Entity\User\User                 $user
     * @param \Pley\Entity\Payment\UserPaymentMethod $existingPaymentMethod
     * @param int                                    $expMonth
     * @param int                                    $expYear
     */
    private function _updateCardClosure(
            \Pley\Payment\PaymentManagerInterface $paymentManager, \Pley\Entity\User\User $user, 
            \Pley\Entity\Payment\UserPaymentMethod $existingPaymentMethod, $expMonth, $expYear)
    {
        $this->_dbManager->checkActiveTransaction(__METHOD__);
        
        $paymentManager->updateCard($user, $existingPaymentMethod, $expMonth, $expYear);
        $this->_userPaymentMethodDao->save($existingPaymentMethod);
    }
    
    /**
     * Closure method to set the supplied payment method as the default for the user.
     * @param \Pley\Payment\PaymentManagerInterface  $paymentManager
     * @param \Pley\Entity\User\User                 $user
     * @param \Pley\Entity\Payment\UserPaymentMethod $paymentMethod
     */
    private function _setDefaultCardClosure(
            \Pley\Payment\PaymentManagerInterface $paymentManager, \Pley\Entity\User\User $user, 
            \Pley\Entity\Payment\UserPaymentMethod $paymentMethod)
    {
        $this->_dbManager->checkActiveTransaction(__METHOD__);
        
        $user->setDefaultPaymentMethodId($paymentMethod->getId());
        $this->_userDao->save($user);
        
        $paymentManager->setDefaultCard($user, $paymentMethod);
    }
    
    /**
     * Closure method to purchase a gift in a transaction
     * @param \Pley\Payment\PaymentManagerInterface $paymentManager
     * @param \Pley\Entity\Gift\Gift $gift
     * @param \Pley\Entity\Subscription\Subscription $subscription
     * @param \Pley\Entity\Gift\GiftPrice $giftPrice
     * @param \Pley\Payment\Method\CreditCard $card
     * @return string The Token for the gift
     */
    private function _purchaseGiftClosure(
            \Pley\Payment\PaymentManagerInterface $paymentManager, 
            \Pley\Entity\Gift\Gift $gift, 
            \Pley\Entity\Subscription\Subscription $subscription,
            \Pley\Entity\Gift\GiftPrice $giftPrice, 
            \Pley\Payment\Method\CreditCard $card)
    {
        $this->_dbManager->checkActiveTransaction(__METHOD__);
        
        // Adding a gift with a new token (Done in a loop in case a duplicate token is somehow generated
        // we can retry with a different token)
        do {
            $token = \Pley\Util\Token::base36();
            $gift->setToken($token);
            $isGiftAdded = false;
            
            try {
                $this->_giftDao->save($gift);
                $isGiftAdded = true;
            } catch (\PDOException $ex) {
                // If the PDO exception is not that of a duplicate entry, then propagate the error
                // otherwise we collided on the Token so we need to try a different token value
                if ($ex->getCode() != 23000) { // Duplicate entry code
                    throw $ex;
                }
            }
        } while (!$isGiftAdded);
        
        $description = sprintf('%s Gift [%s -> %s]', $subscription->getName(), $gift->getFromEmail(), $gift->getToEmail());
        $metadata    = [
            'giftId'           => $gift->getId(),
            'token'            => $gift->getToken(),
            'subscriptionId'   => $subscription->getId(),
            'subscriptionName' => $subscription->getName(),
            'fromEmail'        => $gift->getFromEmail(),
            'fromName'         => $gift->getFromFirstName() . ' ' . $gift->getFromLastName(),
            'toEmail'          => $gift->getToEmail(),
            'toName'           => $gift->getToFirstName() . ' ' . $gift->getToLastName(),
        ];
        
        // Performing the charge
        $transaction = $paymentManager->singleCharge($card, $giftPrice->getPriceTotal(), $description, $metadata);
        
        // Updating the gift with the vendor transaction data
        $gift->setVPaymentTransaction($transaction->getVendorSystemId(), $transaction->getVendorId());
        $this->_giftDao->save($gift);
        
        return $gift->getToken();
    }
}
