<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\NatGeo;

use \Pley\NatGeo\_NG_PrefixHelper as PrefixHelper;

/**
 * The <kbd>NatGeo</kbd> class is the main API interface.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.NatGeo
 * @subpackage NatGeo
 */
class NatGeo
{
    const VERSION = 'v1';
    
    /** @var \Pley\NatGeo\Auth\Auth */
    private static $AUTH = null;
    
    private static $COMMAND_GET_USER_LIST  = 'getUserList';
    private static $COMMAND_ADD_USER       = 'addUser';
    private static $COMMAND_DELETE_USER    = 'deleteUser';
    private static $COMMAND_USER_INFO      = 'getUserInfo';
    private static $COMMAND_ADD_MISSION    = 'assignMissionsToUser';
    private static $COMMAND_REMOVE_MISSION = 'removeMissionsFromUser';
    private static $COMMAND_GET_LOGIN_URL  = 'getLoginURL';

    public static function setApiKey(\Pley\NatGeo\Auth\Auth $auth, $userPrefix = null)
    {
        static::$AUTH = $auth;
        
        PrefixHelper::initPrefix($userPrefix);
    }
    
    /**
     * Returns a list of Users registered on this environment.
     * <p>The list is filtered by the given environment prefix.</p>
     * @return int[]
     */
    public function getUserList()
    {
        $responseMap = $this->_postCommand(static::$COMMAND_GET_USER_LIST);
        
        // From the response, filter out only the users that matched this environment Prefix
        $userIdList = [];
        foreach ($responseMap['ids'] as $userId) {
            $unprefixedUserId = PrefixHelper::unprefix($userId);
            
            if ($unprefixedUserId !== false) {
                $userIdList[] = $unprefixedUserId;
            }
        }
        
        return $userIdList;
    }
    
    /**
     * Creates a new User on the NatGeo system
     * @param int $userId
     * @return boolean <kbd>true</kbd> on success, otherwise an exception is thrown.
     */
    public function addUser($userId)
    {
        $dataMap = ['userID' => PrefixHelper::prefix($userId)];
        
        $this->_postCommand(static::$COMMAND_ADD_USER, $dataMap);
        
        // The response from the Add call has no information to be parsed
        return true;
    }
    
    /**
     * Retruns information about a NatGeo user.
     * @param int $userId
     * @return \Pley\NatGeo\User
     */
    public function getUser($userId)
    {
        $dataMap = ['userID' => PrefixHelper::prefix($userId)];
        
        $responseMap = $this->_postCommand(static::$COMMAND_USER_INFO, $dataMap);
        
        $user = new User($userId, $responseMap['dateCreated']);
        foreach ($responseMap['enabledMissions'] as $missionId => $dateList) {
            list($missionCreatedAt, $missionCompletedAt) = $dateList;
            
            $mission = new Mission($missionId, $missionCreatedAt);
            $mission->setCompletedAt($missionCompletedAt);
            
            $user->addMission($mission);
        }
        
        return $user;
    }
    
    /**
     * Obtain the Game Session URL Link specific to the user
     * @param int    $userId
     * @param string $returnUrl URL for the game to redirect when the user logs-off from the game
     * @return boolean
     */
    public function getGameLoginUrl($userId, $returnUrl)
    {
        $dataMap = [
            'userID'    => PrefixHelper::prefix($userId),
            'returnURL' => $returnUrl,
        ];
        
        $responseMap = $this->_postCommand(static::$COMMAND_GET_LOGIN_URL, $dataMap);
        
        $loginUrl = $responseMap['url'];
        
        return $loginUrl;
    }
    
    /**
     * Adds a mission to the User on the NatGeo system.
     * <p>Note: if the mission has already been added, this call will have no effect, but also no
     * error would be thrown.</p>
     * @param int $userId
     * @param int $missionId
     * @return boolean <kbd>true</kbd> on success, otherwise an exception is thrown.
     */
    public function addMission($userId, $missionId)
    {
        $dataMap = [
            'userID'     => PrefixHelper::prefix($userId),
            'missionIDs' => $missionId,
        ];
        
        $this->_postCommand(static::$COMMAND_ADD_MISSION, $dataMap);
        
        // The response from the Add call has no information to be parsed
        return true;
    }
    
    /**
     * Helper method to sanitize the UserId with a prefix if one was supplied during initialization
     * @param int $userId
     * @return string
     */
    protected function _getSanitizedUserId($userId)
    {
        if (!empty(static::$USER_PREFIX)) {
            return static::$USER_PREFIX . $userId;
        }
        
        return $userId;
    }
    
    /**
     * Makes a Post request to the API with the supplied command and data map and returns the respective
     * command result.
     * @param string $command
     * @param array  $dataMap
     * @return array
     */
    protected function _postCommand($command, $dataMap = null)
    {
        if (empty($dataMap)) {
            $dataMap = [];
        }
        
        $dataMap['cmd'] = $command;
        
        $reqClient = $this->_getRequestClient();
        
        $response = $reqClient->post($dataMap);
        return $response;
    }
    
    /**
     * Returns an Http Request client to perform calls to the NatGeo API.
     * @return \Pley\NatGeo\Http\Request
     */
    protected function _getRequestClient()
    {
        // Declaring this class variable locally as I am not looking forward for this variable to
        // be used directly without going through this method to avoid other calls forgetting to
        // initialize it or modifying the value accidentally.
        // This also allows for some sort of caching of the Request object as well as mocking for tests.
        if (!isset($this->_requestClient)) {
            $this->_requestClient = new \Pley\NatGeo\Http\Request(static::$AUTH);
        }
        
        return $this->_requestClient;
    }
}


class _NG_PrefixHelper
{
    const SEPARATOR = '#::';
    
    private static $_prefix;
    
    public static function initPrefix($prefix)
    {
        static::$_prefix = empty($prefix)? null : $prefix;
    }
    
    /**
     * Indicates whether the supplied user ID has a prefix or not.
     * <p>Makes no checks to match against the initialized prefix, its only purpose is to indicate
     * if there is an existing prefix or not.</p>
     * @param int|string $userId
     * @return boolean <kbd>true</kbd> if it has a prefix, <kbd>false</kbd> otherwise.
     */
    public static function hasPrefix($userId)
    {
        return strpos($userId, static::SEPARATOR) !== false;
    }
    
    /**
     * Prefixes the supplied user ID if a prefix was initialized and the value has not already been
     * prefixed.
     * @param int|string $userId
     * @return int|string
     */
    public static function prefix($userId)
    {
        if (empty(static::$_prefix) || static::hasPrefix($userId)) {
            return $userId;
        }
        
        // Now we are good to prefix
        return static::$_prefix . static::SEPARATOR .  $userId;
    }
    
    /**
     * Removes a prefix from the supplied user ID if there is any and it matches the initialized prefix,
     * otherwise <kbd>false</kbd> is returned.
     * @param int|string $userId
     * @return int|boolean
     */
    public static function unprefix($userId)
    {
        if (empty(static::$_prefix)) {
            if (static::hasPrefix($userId)) {
                return false;
            }
            
            return (int)$userId;
            
        } else {
            if (!static::hasPrefix($userId)) {
                return false;
            }
            
            list($prefix, $intUserId) = explode(static::SEPARATOR, $userId);
            if ($prefix != static::$_prefix) {
                return false;
            }
            
            return (int)$intUserId;
        }
    }
}