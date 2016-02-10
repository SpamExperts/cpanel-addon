<?php
/*
*************************************************************************
*                                                                       *
* ProSpamFilter                                                         *
* Bridge between Webhosting panels & SpamExperts filtering			    *
*                                                                       *
* Copyright (c) 2010-2011 SpamExperts B.V. All Rights Reserved,         *
*                                                                       *
*************************************************************************
*                                                                       *
* Email: support@spamexperts.com                                        *
* Website: htttp://www.spamexperts.com                                  *
*                                                                       *
*************************************************************************
*                                                                       *
* This software is furnished under a license and may be used and copied *
* only in accordance with the  terms of such license and with the       *
* inclusion of the above copyright notice. No title to and ownership    *
* of the software is  hereby  transferred.                              *
*                                                                       *
* You may not reverse engineer, decompile or disassemble this software  *
* product or software product license.                                  *
*                                                                       *
* SpamExperts may terminate this license if you don't comply with any   *
* of the terms and conditions set forth in our end user                 *
* license agreement (EULA). In such event, licensee agrees to return    *
* licensor  or destroy  all copies of software upon termination of the  *
* license.                                                              *
*                                                                       *
* Please see the EULA file for the full End User License Agreement.     *
*                                                                       *
*************************************************************************
* @category  SpamExperts
* @package   ProSpamFilter
* @author    $Author$
* @copyright Copyright (c) 2011, SpamExperts B.V., All rights Reserved. (http://www.spamexperts.com)
* @license   Closed Source
* @version   3.0
* @link      https://my.spamexperts.com/kb/34/Addons
* @since     2.0
*/
class SpamFilter_Validate_ApiCredentials extends Zend_Validate_Abstract
{

	/**
 	* @param $_username Username
 	* @access private
 	*/
	private $_username;

	/**
 	* @param $_hostname Hostname
 	* @access private
 	*/
	private $_hostname;

	/**
 	* @param $_password Password
 	* @access private
 	*/
	private $_password;

	/**
 	* @param $_sslenabled SSL Enabled true / false
 	* @access private
 	*/
	private $_sslenabled;

	const INVALID_API = 'invalidApi';
	const INVALID_CREDENTIALS = 'invalidCredentials';
	const INTERNAL_ERROR = "internalError";

	protected $_messageTemplates = array(
		self::INVALID_API => "The API is unreachable",
		self::INVALID_CREDENTIALS => "Your API credentials are not valid.",
		self::INTERNAL_ERROR => "An internal error has occurred.",
	);

/**
 * __construct
 * Constructor of validator
 *
 * @param $hostname Hostname to validate
 * @param $username Username to validate
 * @param $password Password  to validate
 * @param $sslenabled SSL enabled
 *
 * @return void
 *
 * @access public
 */
	public function __construct($hostname, $username, $password, $sslenabled)
	{
		$this->_hostname = (string)$hostname;
		$this->_username = (string)$username;
		$this->_password = (string)$password;
		$this->_sslenabled = (bool)$sslenabled;
	}

/**
 * isValid
 * Validate constructed input
 *
 * @param $value Value to validate
 *
 * @return bool Status
 *
 * @access public
 * @see _error()
 */
	public function isValid($value)
	{
		if ( (!isset($this->_hostname)) || (!isset($this->_username)) || (!isset($this->_password)) || (!isset($this->_sslenabled)) )
		{
			Zend_Registry::get('logger')->debug("[ApiCredentials] Internal error occurred, empty values provided.");
			$this->_error( self::INTERNAL_ERROR );
			return false;
		}

        // we should make sure the hostname is valid
        $hostnameValidator = new SpamFilter_Validate_Hostname();
		if (!$hostnameValidator->isValid($this->_hostname)) {
            Zend_Registry::get('logger')->debug("[ApiCredentials] Can't call the API because hostname '{$this->_hostname}' is invalid.");
            $this->_error( self::INVALID_API );
            return false;
        }

		// We cannot provide custom credentials for the API anymore
		$url = ($this->_sslenabled) ? 'http://' : 'https://';
		$url .= $this->_hostname . '/api/version/get/format/json/';
		
		Zend_Registry::get('logger')->debug("[ApiCredentials] Using test URL: '{$url}'");		
    	$config = new stdClass();
    	$config->apiuser = $this->_username;
    	$config->apipass = $this->_password;	
    	$content = trim( SpamFilter_HTTP::getContent($url, $config) );
		Zend_Registry::get('logger')->debug("[ApiCredentials] Content retrieved: '{$content}'");

		if ( empty( $content ) )
		{
			Zend_Registry::get('logger')->debug("[ApiCredentials] Return values empty.");
			$this->_error( self::INVALID_CREDENTIALS );
			return false;
		}

		if ( is_array($content) )
		{
			if(isset($content['reason']) && ($content['reason'] == "API_REQUEST_FAILED"))
			{
				Zend_Registry::get('logger')->debug("[ApiCredentials] Test returned an error array.");
				$this->_error( self::INVALID_CREDENTIALS );
				return false;
			}
		}

		$string = Zend_Json::decode($content);

		if(empty($string)) {
			Zend_Registry::get('logger')->debug("[ApiCredentials] Improper data returned.");
			$this->_error( self::INVALID_CREDENTIALS );
			return false;
		}

        if (array_key_exists('error', $string['messages'])) {
            if (stristr($string['messages']['error'][0], "no access to this API, current IP address")) {
                // we should save the settings since they are correct, the API rejects its ip
                return true;
            } elseif (stristr($string['messages']['error'][0], "has status 'inactive'")) {
                // we should save the settings since they are correct, the user is set as inactive
                return true;
            } else {
                Zend_Registry::get('logger')->debug("[ApiCredentials] API returned error: " . $string['messages']['error'][0]);
                $this->_error( self::INVALID_CREDENTIALS );
                return false;
            }
        }

		Zend_Registry::get('logger')->debug("[ApiCredentials] Completed successfully");
		return true;
	}
}
