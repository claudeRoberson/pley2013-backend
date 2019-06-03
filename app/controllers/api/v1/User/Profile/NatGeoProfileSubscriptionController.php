<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace api\v1\User\Profile;

use \Pley\Config\ConfigInterface as Config;

/**
 * @author Alejandro Salazar (alejandros@pley.com)
 */
class NatGeoProfileSubscriptionController extends \api\v1\BaseAuthController
{
    /** @var \Pley\Config\ConfigInterface */
    protected $_config;
    /** @var \Pley\Dao\Profile\ProfileSubscriptionDao */
    protected $_profileSubsDao;
    
    public function __construct(Config $config,
            \Pley\Dao\Profile\ProfileSubscriptionDao $profileSubsDao)
    {
        parent::__construct();
        
        $this->_config         = $config;
        $this->_profileSubsDao = $profileSubsDao;
    }
    
    // GET nat-geo/subscription/{intId}/experience-url
    public function getExperienceUrl($profileSubscriptionId)
    {
        \RequestHelper::checkGetRequest();
        
        $profileSubscription = $this->_profileSubsDao->find($profileSubscriptionId);
        \ValidationHelper::entityExist($profileSubscription, \Pley\Entity\Profile\ProfileSubscription::class);
        
        // Make sure that the requested subscription belongs to the logged in user
        if ($profileSubscription->getUserId() != $this->_user->getId()) {
            throw new \Exception("Profile Subscription `{$profileSubscriptionId}` does not belong to supplied User.");
        }
        
        $natGeo = new \Pley\NatGeo\NatGeo();
        
        $ngUser    = $this->_getNatGeoUser($profileSubscriptionId);
        $returnUrl = $this->_getFrontendBaseUrl() . '/tb/my-account';
        $loginUrl  = $natGeo->getGameLoginUrl($ngUser->getId(), $returnUrl);

        return \Response::json(['loginUrl' => $loginUrl]);
    }
    
    /**
     * Returns the NatGeo user object for the supplied profile subscription
     * @param int $profileSubscriptionId
     * @return \Pley\NatGeo\User
     */
    private function _getNatGeoUser($profileSubscriptionId)
    {
        $natGeo = new \Pley\NatGeo\NatGeo();
        $ngUserId = $profileSubscriptionId;
        
        // Retrieve NatGeo user, or create it if it doesn't exist yet
        try {
            $ngUser = $natGeo->getUser($ngUserId);
            
        } catch(\Pley\NatGeo\Exception\UserDoesntExistException $ude) {
            $natGeo->addUser($ngUserId);
            $ngUser = $natGeo->getUser($ngUserId);
        }
        
        return $ngUser;
    }
    
    private function _getFrontendBaseUrl()
    {
        $protocol = $this->_config->get('mailTemplate.siteUrl.pley.protocol');
        $domain   = $this->_config->get('mailTemplate.siteUrl.pley.domain');
        
        return $protocol . '://' . $domain;
    }
}
