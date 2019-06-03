<?php /** @copyright Pley (c) 2016, All Rights Reserved */

namespace api\v1\User;

use \Pley\Config\ConfigInterface as Config;
use \Pley\Mail\AbstractMail as Mail;

/**
 * The <kbd>UserAddressController</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 */
class UserAddressController extends \api\v1\BaseAuthController
{
    /** @var \Pley\Db\AbstractDatabaseManager */
    protected $_dbManager;
    /** @var \Pley\Dao\User\UserAddressDao */
    protected $_userAddressDao;
    /** @var \Pley\Dao\Profile\ProfileSubscriptionDao */
    protected $_profileSubsDao;
    /** @var \Pley\Shipping\AbstractShipmentManager */
    protected $_shipmentMgr;
    /** @var \Pley\Config\ConfigInterface */
    protected $_config;
    /** @var \Pley\Mail\AbstractMail */
    protected $_mail;

    public function __construct(
        Config $config,
        Mail $mail,
        \Pley\Db\AbstractDatabaseManager $dbManager,
        \Pley\Dao\User\UserAddressDao $userAddressDao,
        \Pley\Dao\Profile\ProfileSubscriptionDao $profileSubsDao,
        \Pley\Shipping\Impl\EasyPost\ShipmentManager $shipmentMgr
    ) {
        parent::__construct();

        $this->_config = $config;
        $this->_mail = $mail;
        $this->_dbManager = $dbManager;

        $this->_userAddressDao = $userAddressDao;
        $this->_profileSubsDao = $profileSubsDao;

        $this->_shipmentMgr = $shipmentMgr;
    }

    // POST /user/address
    public function add()
    {
        \RequestHelper::checkPostRequest();
        \RequestHelper::checkJsonRequest();

        $json = \Input::json()->all();
        $newUserAddress = $this->_validateAddress($json);

        // Adding the zone to the validated address
        $this->_shipmentMgr->assignShippingZones($newUserAddress);
        $this->_userAddressDao->save($newUserAddress);

        $arrayResponse = $this->_parseUserAddress($newUserAddress);
        return \Response::json($arrayResponse);
    }

    // GET /user/address/{userAddressId}
    public function get($userAddressId)
    {
        \RequestHelper::checkGetRequest();
        $userAddress = $this->_userAddressDao->find($userAddressId);
        \ValidationHelper::entityExist($userAddress, \Pley\Entity\User\UserAddress::class);
        if ($userAddress->getUserId() != $this->_user->getId()) {
            throw new \Exception('Mismatching relationship');
        }
        $arrayResponse = $this->_parseUserAddress($userAddress);
        return \Response::json($arrayResponse);
    }

    // PUT /user/address/{userAddressId}
    public function update($userAddressId)
    {
        \RequestHelper::checkPutRequest();
        \RequestHelper::checkJsonRequest();

        $json = \Input::json()->all();
        $updateUserAddress = $this->_validateAddress($json, $userAddressId);

        $existingUserAddress = $this->_userAddressDao->find($userAddressId);
        \ValidationHelper::entityExist($existingUserAddress, \Pley\Entity\User\UserAddress::class);

        // This is just a validation for attack attempts, where a logged in user is trying to update
        // a different user's address by the address ID.
        if ($existingUserAddress->getUserId() != $this->_user->getId()) {
            throw new \Exception('Mismatching relationship');
        }

        $this->_shipmentMgr->assignShippingZones($updateUserAddress);
        $updateUserAddress->setId($existingUserAddress->getId());
        $this->_userAddressDao->save($updateUserAddress);

        $arrayResponse = $this->_parseUserAddress($updateUserAddress);
        return \Response::json($arrayResponse);
    }

    // POST /user/address/{userAddressId}/notify
    public function notifyInvalidAddress($userAddressId)
    {
        \RequestHelper::checkPostRequest();
        \RequestHelper::checkJsonRequest();
        $json = \Input::json()->all();
        $message = isset($json['message']) ? $json['message'] : '';

        $existingUserAddress = $this->_userAddressDao->find($userAddressId);
        \ValidationHelper::entityExist($existingUserAddress, \Pley\Entity\User\UserAddress::class);

        // This is just a validation for attack attempts, where a logged in user is trying to update
        // a different user's address by the address ID.
        if ($existingUserAddress->getUserId() != $this->_user->getId()) {
            throw new \Exception('Mismatching relationship');
        }

        $this->_sendUserAddressValidationFailureEmail($this->_user, $existingUserAddress, $message);

        return \Response::json([
            'success' => true,
            'userAddressId' => $userAddressId,
        ]);
    }

    // DELETE /user/address/{userAddressId}
    public function remove($userAddressId)
    {
        \RequestHelper::checkDeleteRequest();

        $existingUserAddress = $this->_userAddressDao->find($userAddressId);
        \ValidationHelper::entityExist($existingUserAddress, \Pley\Entity\User\UserAddress::class);

        // This is just a validation for attack attempts, where a logged in user is trying to update
        // a different user's address by the address ID.
        if ($existingUserAddress->getUserId() != $this->_user->getId()) {
            throw new \Exception('Mismatching relationship');
        }

        $profileSubsList = $this->_profileSubsDao->findByUser($this->_user->getId());
        foreach ($profileSubsList as $profileSubscrition) {
            // If the subscription is not related to the address the user want to remove, skip it
            if ($profileSubscrition->getUserAddressId() != $userAddressId) {
                continue;
            }

            // If the subscription is related, if the subscription is active, then we cannot delete
            if ($profileSubscrition->getStatus() != \Pley\Enum\SubscriptionStatusEnum::CANCELLED) {
                throw new \Pley\Exception\User\AddressDeleteException($this->_user, $existingUserAddress);
            }
        }

        if (count($this->_userAddressDao->findByUser($this->_user->getId())) === 1) {
            throw new \Pley\Exception\User\AddressNonDeletableException($this->_user, $existingUserAddress);
        }
        // Proceed to reset the cancelled subscriptions address and delete the address.
        $that = $this;
        $this->_dbManager->transaction(function () use ($that, $existingUserAddress, $profileSubsList) {
            $that->_removeClosure($existingUserAddress, $profileSubsList);
        });

        return \Response::json([
            'success' => true,
            'userAddressId' => $userAddressId,
        ]);
    }

    /**
     * Closure to reset cancelled subscriptions address and delete the supplied address, as a transaction.
     * @param \Pley\Entity\User\UserAddress $existingUserAddress
     * @param \Pley\Entity\Profile\ProfileSubscription[] $profileSubsList
     */
    private function _removeClosure(\Pley\Entity\User\UserAddress $existingUserAddress, array $profileSubsList)
    {
        $this->_dbManager->checkActiveTransaction(__METHOD__);

        // Resetting the address of Cancelled subscriptions.
        foreach ($profileSubsList as $profileSubscription) {
            // Skip subscriptions that are not related to the address the user wants to remove and
            // also those that are active
            if ($profileSubscription->getUserAddressId() != $existingUserAddress->getId()
                || $profileSubscription->getStatus() != \Pley\Enum\SubscriptionStatusEnum::CANCELLED
            ) {
                continue;
            }

            // Now that we know we have a subscription that is related and cancelled we can reset it
            $profileSubscription->setUserAddressId(null);
            $this->_profileSubsDao->save($profileSubscription);
        }

        // Finally remove the address requested
        $this->_userAddressDao->delete($existingUserAddress);
    }

    /**
     * Validates that the address array data is valid in values and shippable destination as well
     * as not duplicated with an existing User address.
     * @param array $addressDataMap
     * @param int $userAddressId (Optional)<br/>If supplied, it is used to exclude the entry we are
     *      trying to check from comparing against itself and thus yielding an exception
     * @return \Pley\Entity\User\UserAddress
     * @throws \Pley\Exception\User\AddressExistingException
     */
    private function _validateAddress(array $addressDataMap, $userAddressId = null)
    {
        \ValidationHelper::validate($addressDataMap, [
            'street1' => 'required',
            'street2' => 'sometimes',
            'phone' => 'sometimes',
            'city' => 'required|alpha_space',
            'state' => 'sometimes',
            'country' => 'required|alpha_space',
            'zip' => 'required'
        ]);

        $inputUserAddress = \Pley\Entity\User\UserAddress::forVerification(
            $addressDataMap['street1'],
            $addressDataMap['street2'],
            (isset($addressDataMap['phone'])) ? $addressDataMap['phone'] : null,
            $addressDataMap['city'],
            $addressDataMap['state'],
            $addressDataMap['country'],
            $addressDataMap['zip']
        );
        $inputUserAddress->setUserId($this->_user->getId());

        // Checking that it is a valid shippable destination
        $this->_shipmentMgr->validateSupportedDestination($inputUserAddress);
        $suggestedUserAddress = $this->_shipmentMgr->verifyAddress($inputUserAddress);
        $this->_shipmentMgr->validateSupportedDestination($suggestedUserAddress);

        // Now validating that the address is not one the user already has
        $userAddressList = $this->_userAddressDao->findByUser($this->_user->getId());
        foreach ($userAddressList as $userAddress) {
            // If a address ID is supplied, then add a check to avoid comparing against itself
            if (isset($userAddressId) && $userAddressId == $userAddress->getId()) {
                continue;
            }

            if ($this->_isSameAddress($userAddress, $suggestedUserAddress)) {
                throw new \Pley\Exception\User\AddressExistingException($this->_user, $userAddress);
            }
        }

        return $suggestedUserAddress;
    }

    /**
     * Compares two validates Addresses for equalty.
     * @param \Pley\Entity\User\UserAddress $address1
     * @param \Pley\Entity\User\UserAddress $address2
     * @return boolean
     */
    private function _isSameAddress(\Pley\Entity\User\UserAddress $address1, \Pley\Entity\User\UserAddress $address2)
    {
        return $address1->getStreet1() == $address2->getStreet1()
            && $address1->getStreet2() == $address2->getStreet2()
            && $address1->getCity() == $address2->getCity()
            && $address1->getState() == $address2->getState()
            && $address1->getZipCode() == $address2->getZipCode()
            && $address1->getCountry() == $address2->getCountry();
    }

    /**
     * Parses a UserAddress object into an associative array for the response
     * @param \Pley\Entity\User\UserAddress $userAddress
     * @return array
     */
    private function _parseUserAddress(\Pley\Entity\User\UserAddress $userAddress)
    {
        return [
            'id' => $userAddress->getId(),
            'street1' => $userAddress->getStreet1(),
            'street2' => $userAddress->getStreet2(),
            'phone' => $userAddress->getPhone(),
            'city' => $userAddress->getCity(),
            'state' => $userAddress->getState(),
            'country' => $userAddress->getCountry(),
            'zip' => $userAddress->getZipCode()
        ];
    }

    /**
     * Helper method to send the Address validation error email to customer support
     * @param \Pley\Entity\User\User $user
     * @param \Pley\Entity\User\UserAddress $userAddress
     * @param $message string
     */
    private function _sendUserAddressValidationFailureEmail(
        \Pley\Entity\User\User $user,
        \Pley\Entity\User\UserAddress $userAddress,
        $message = ''
    ) {
        $mailTagCollection = new \Pley\Mail\MailTagCollection($this->_config);
        $mailTagCollection->addEntity($user);
        $mailTagCollection->addEntity($userAddress);
        $mailTagCollection->setCustom('customerMessage', nl2br($message));

        $mailUserTo = new \Pley\Mail\MailUser($this->_config->get('mailTemplate.customerSupportEmail'), 'Pley CS');
        $this->_mail->send(\Pley\Enum\Mail\MailTemplateEnum::ADDRESS_VALIDATION_FAILURE, $mailTagCollection,
            $mailUserTo);
    }
}
