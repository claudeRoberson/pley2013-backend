<?php /** @copyright Pley (c) 2015, All Rights Reserved */
namespace api\v1\User\Profile;

/**
 * The <kbd>UserRegistrationController</kbd> takes care of adding new users.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package api.v1
 */
class UserProfileController extends \api\v1\BaseAuthController
{
    /** @var \Pley\Dao\User\UserProfileDao **/
    protected $_userProfileDao;
    /** @var \Pley\Dao\Profile\ProfileSubscriptionDao */
    protected $_profileSubsDao;
    
    public function __construct(
            \Pley\Dao\User\UserProfileDao $userProfileDao,
            \Pley\Dao\Profile\ProfileSubscriptionDao $profileSubsDao)
    {
        parent::__construct();
        
        $this->_userProfileDao = $userProfileDao;
        $this->_profileSubsDao = $profileSubsDao;
    }

    // POST /user/profile
    public function add()
    {
        \RequestHelper::checkPostRequest();
        \RequestHelper::checkJsonRequest();
        
        $json = \Input::json()->all();
        
        $this->_validateProfile($json);
        
        $userProfile = \Pley\Entity\User\UserProfile::withNew(
            $this->_user->getId(),                 
            $json['gender'],
            $json['shirtSize'],
            $json['firstName'],
            isset($json['lastName'])? $json['lastName'] : null,
            isset($json['birthDate'])? $json['birthDate'] : null
        );
        
        $this->_userProfileDao->save($userProfile);
        
        return \Response::json(['id' => $userProfile->getId()]);
    }

    // PUT /user/profile/{profileId}
    public function update($profileId)
    {
        \RequestHelper::checkPutRequest();
        \RequestHelper::checkJsonRequest();
        
        $json = \Input::json()->all();
        
        $this->_validateProfile($json, $profileId);
        
        $userProfile = $this->_userProfileDao->find($profileId);
        \ValidationHelper::entityExist($userProfile, \Pley\Entity\User\UserProfile::class);
        
        // This is just a validation for attack attempts, where a logged in user is trying to update
        // a different user's address by the address ID.
        if ($userProfile->getUserId() != $this->_user->getId()) {
            throw new \Exception('Mismatching relationship');
        }
        
        $userProfile->setFirstName($json['firstName']);
        $userProfile->setGender($json['gender']);
        $userProfile->setTypeShirtSizeId($json['shirtSize']);
        
        if (isset($json['lastName'])) { $userProfile->setLastName($json['lastName']); }
        if (isset($json['birthDate'])) { $userProfile->setBirthDate($json['birthDate']); }
        
        $this->_userProfileDao->save($userProfile);
        
        return \Response::json(['id' => $profileId]);
    }

    // PUT /user/profile/{profileId}
    public function remove($profileId)
    {
        \RequestHelper::checkDeleteRequest();
        
        $userProfile = $this->_userProfileDao->find($profileId);
        \ValidationHelper::entityExist($userProfile, \Pley\Entity\User\UserProfile::class);
        
        // This is just a validation for attack attempts, where a logged in user is trying to update
        // a different user's address by the address ID.
        if ($userProfile->getUserId() != $this->_user->getId()) {
            throw new \Exception('Mismatching relationship');
        }
        
        // Since a profile can only be removed if it has never been used, we need to check that
        $isProfileUsed = false;
        $subsList      = $this->_profileSubsDao->findByUser($this->_user->getId());
        foreach ($subsList as $subscription) {
            if ($subscription->getUserProfileId() == $profileId) {
                $isProfileUsed = true;
                break;
            }
        }
        
        // If the profile was used at least once, then we throw an exception.
        if ($isProfileUsed) {
            throw new \Pley\Exception\User\ProfileDeleteException($this->_user, $userProfile);
        }

        if (count($this->_userProfileDao->findByUser($this->_user->getId())) === 1){
            throw new \Pley\Exception\User\ProfileNonDeletableException($this->_user, $userProfile);
        };
        
        $this->_userProfileDao->delete($userProfile);
        
        return \Response::json(['id' => $profileId]);
    }
    
    /**
     * Validates that the Profile data is correct and there is no other profile with the exact same
     * name already (to avoid duplicates)
     * @param array $profileData
     * @param int   $profileId   (Optional)<br/>If supplied, it is used to exclude the entry we are
     *      trying to check from comparing against itself and thus yielding an exception
     * @throws \Pley\Exception\User\ProfileExistingException If duplicate profile is found.
     */
    private function _validateProfile($profileData, $profileId = null)
    {
        \ValidationHelper::validate($profileData, [
            'gender'    => 'required|in:male,female',
            'firstName' => 'required|string',
            'shirtSize' => 'required|integer',
            'lastName'  => 'sometimes|string',
            'birthDate' => 'sometimes|date|date_format:Y-m-d',
        ]);
        
        $userProfile = \Pley\Entity\User\UserProfile::withNew(
            $this->_user->getId(),                 
            $profileData['gender'],
            $profileData['shirtSize'],
            $profileData['firstName'],
            isset($profileData['lastName'])? $profileData['lastName'] : null,
            isset($profileData['birthDate'])? $profileData['birthDate'] : null
        );
        
        $profileList = $this->_userProfileDao->findByUser($this->_user->getId());
        foreach ($profileList as $existingProfile) {
            // If a profile ID is supplied, then add a check to avoid comparing against itself
            if (isset($profileId) && $profileId == $existingProfile->getId()) {
                continue;
            }
            
            if ($this->_isSameProfile($userProfile, $existingProfile)) {
                throw new \Pley\Exception\User\ProfileExistingException($this->_user, $existingProfile);
            }
        }
    }
    
    /**
     * Compares two profiles for name equalty.
     * @param \Pley\Entity\User\UserProfile $profile1
     * @param \Pley\Entity\User\UserProfile $profile2
     * @return boolean
     */
    private function _isSameProfile(\Pley\Entity\User\UserProfile $profile1, \Pley\Entity\User\UserProfile $profile2)
    {
        $profile1Name = strtolower($profile1->getFirstName() . ' ' . $profile1->getLastName());
        $profile2Name = strtolower($profile2->getFirstName() . ' ' . $profile2->getLastName());
        
        // Removing any multiple spaces and any spaces at the end and beginning
        $profile1Name = trim(preg_replace('/ {2,}/', ' ', $profile1Name));
        $profile2Name = trim(preg_replace('/ {2,}/', ' ', $profile2Name));
        
        return $profile1Name == $profile2Name;
    }
}

