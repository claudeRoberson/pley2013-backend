<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\NatGeo\Http;

/**
 * The <kbd>Request</kbd> class provides connectivity to NatGeo API
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.NatGeo.Http
 * @subpackage NatGeo
 * @subpackage Http
 */
class Request
{
    const METHOD_GET    = 'get';
    const METHOD_POST   = 'post';
    const METHOD_PUT    = 'put';
    const METHOD_DELETE = 'delete';

    /** @var string */
    private static $BASE_URI = 'https://nat-geo.pley.com/main/api';
    
    /** @var \Pley\NatGeo\Auth\Auth */
    private $_auth;
    /** @var \GuzzleHttp\Client */
    protected $_httpClient;
    
    /**
     * Constructor.
     * @param \Pley\NatGeo\Auth\Auth $auth
     */
    public function __construct(\Pley\NatGeo\Auth\Auth $auth)
    {
        $this->_auth   = $auth;

        // Please note that the last `/` is very important so that the last section in the base URI
        // is not replaced by partial URI requests due to PSR7 standards.
        $baseUri = static::$BASE_URI . '/';

        $this->_httpClient = new \GuzzleHttp\Client(['base_uri' => $baseUri]);
    }
    
    /**
     * Makes a GET Request and returns the resulting array map response.
     * @return array 
     */
    public function get()
    {
        return $this->_request(self::METHOD_GET, '');
    }
    
    /**
     * Makes a POST Request with the given data and returns the resulting array map response.
     * @param array  $dataMap (Optional)
     * @return array 
     */
    public function post($dataMap = null)
    {
        return $this->_request(self::METHOD_POST, '', $dataMap);
    }
    
    /**
     * Makes a PUT Request with the given data and returns the resulting array map response.
     * @param array  $dataMap (Optional)
     * @return array 
     */
    public function put($dataMap = null)
    {
        return $this->_request(self::METHOD_PUT, '', $dataMap);
    }
    
    /**
     * Makes a DELETE Request with the given data if supplied and returns the resulting array map response.
     * @param array  $dataMap (Optional)
     * @return array 
     */
    public function delete($dataMap = null)
    {
        return $this->_request(self::METHOD_DELETE, '', $dataMap);
    }
    
    /**
     * Delegate to make a Request to the supplied URI by the supplied method, with the given data if
     * supplied and returns the resulting array map response.
     * @param string $method
     * @param string $uri
     * @param array  $dataMap (Optional)
     * @return array 
     */
    protected function _request($method, $uri = '', $dataMap = null)
    {
        $clientOptions = $this->_getDefaultOptions();
        
        $this->_setFormParams($clientOptions, $dataMap);
        
        try {
            $response = $this->_httpClient->request($method, $uri, $clientOptions);
        } catch (\Exception $e) {
            $this->_handleException($e);
            throw $e; // Safety check in case a future modification removes the exception thrown
                      // by the _handleException() method, should technically never execute.
        }
        
        $responseBody   = (string) $response->getBody();
        $parsedResponse = \GuzzleHttp\json_decode($responseBody, true);

        // Since the API does not usually return HTTP 400 status for bad requests, we need to check
        // the response object to see if there was a problem
        $this->_verifyBadRequest($parsedResponse);
        
        return $parsedResponse;
    }
    
    /**
     * Handles an exception thrown by Guzzle (which could be from connectivity errors to responses
     * that use the HTTP 400s status)
     * @param \GuzzleHttp\Exception\ClientException $e
     * @throws \Exception The GuzzleException if not a client type of exception, the GuzzleException
     *      for a non-recognizable client exception, or one of our internal exception that represent
     *      the captured response.
     */
    protected function _handleException(\Exception $e)
    {
        // If not a response exception (HTTP 400s), then just propagate
        if (!$e instanceof \GuzzleHttp\Exception\ClientException) {
            throw $e;
        }
        
        // Now we can proceed to handle the known exceptions
        /* @var $e \GuzzleHttp\Exception\ClientException */
        
        $errJsonStr = (string) $e->getResponse()->getBody();
        $errMap     = \GuzzleHttp\json_decode($errJsonStr, true);
        
        throw new \RuntimeException($errMap['Message']);
    }
    
    /**
     * Checks if the response indicates an unsuccessful request and if so, a matching exception will
     * be raised.
     * @param array $responseMap The data from the parsed response
     * @param array $dataMap     (Optional)<br/>The data supplied for the request
     * @throws \Pley\NatGeo\Exception\InvalidRequestException
     */
    protected function _verifyBadRequest($responseMap, $dataMap = null)
    {
        // If response was successful, no need to do any Exception matching
        if ($responseMap['success']) {
            return;
        }
        
        $errMessage = strtolower($responseMap['message']);
        
        $checkList = [
            ['Invalid request', \Pley\NatGeo\Exception\InvalidRequestException::class],
            ['command is not supported', \Pley\NatGeo\Exception\CommandNotSupportedException::class],
            ['Invalid mission IDs', \Pley\NatGeo\Exception\InvalidMissionIdException::class],
            ['User already exists', \Pley\NatGeo\Exception\UserExistsException::class],
            ['userID required', \Pley\NatGeo\Exception\RequiredUserIdException::class],
            ['User with ID', \Pley\NatGeo\Exception\UserDoesntExistException::class],
            ['database error', \Pley\NatGeo\Exception\InternalServerException::class],
        ];
        
        foreach ($checkList as $checkData) {
            list($checkMessage, $exClass) = $checkData;
            $this->_checkHandleException($errMessage, $checkMessage, $exClass, $dataMap);
        }
        
        // If no previous error message match, either we don't know how to handle it
        throw new \Pley\NatGeo\Exception\InvalidRequestException($dataMap);
    }
    
    /**
     * Return a map of default options to be passed on all Requests.
     * <p>Can also be used for debugging purposes.</p>
     * @return array
     */
    protected function _getDefaultOptions()
    {
        $optionsMap = [
            'form_params' => [
                'portalID' => $this->_auth->getPortalId(),
                'password' => $this->_auth->getPassword(),
            ],
        ];
        
        return $optionsMap;
    }
    
    /**
     * Appends request data to the Options Map to be sent to the API
     * @param array $optionsMap The source options map to append data to
     * @param array $dataMap    (Optional)
     */
    protected function _setFormParams(&$optionsMap, $dataMap = null)
    {
        // Since a `FORM` request is needed, Guzzle needs the following parameter to be set to correctly
        // add the `Content-type: application/x-www-form-urlencoded`
        // So, if it is not Set for any reason, initialize it.
        if (!isset($optionsMap['form_params'])) {
            $optionsMap['form_params'] = [];
        }
        
        if (empty($dataMap)) {
            return;
        }
        
        $optionsMap['form_params'] = array_merge($optionsMap['form_params'], $dataMap);
    }
    
    /**
     * If the Error Message matches the Check message, a new exception is thrown with the indicated class.
     * @param string $errMessage
     * @param string $checkMessage
     * @param string $exceptionClass
     * @param array $dataMap         (Optional)
     */
    protected function _checkHandleException($errMessage, $checkMessage, $exceptionClass, $dataMap = null)
    {
        $errMsg   = strtolower($errMessage);
        $checkMsg = strtolower($checkMessage);

        if (strpos($errMsg, $checkMsg) !== false) {
            throw new $exceptionClass($dataMap);
        }
        
    }
}
