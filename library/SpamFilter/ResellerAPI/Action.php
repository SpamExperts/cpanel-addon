<?php
/*
*************************************************************************
*                                                                       *
* ProSpamFilter                                                         *
* Bridge between Webhosting panels & SpamExperts filtering		*
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
*/

/**
 * SpamPanel API Wrapper Action handler
 *
 * This is the actual "brains" behind the API integration. It gets its orders from the SpamFilter_ResellerAPI
 *
 * @class     SpamFilter_ResellerAPI_Action
 * @category  SpamExperts
 * @package   ProSpamFilter
 * @author    $Author$
 * @copyright Copyright (c) 2011, SpamExperts B.V., All rights Reserved. (http://www.spamexperts.com)
 * @license   Closed Source
 * @version   3.0
 * @link      https://my.spamexperts.com/kb/34/Addons
 * @since     3.0
 */

/**
 * @method string add()
 * @method string remove()
 * @method string getroute()
 * @method string move()
 * @method string edit()
 * @method string set()
 * @method string getfreelimit()
 * @method string adminadd()
 * @method string get()
 * @method string create()
 */


/**
 * @method string add()
 * @method string move()
 * @method string getroute()
 * @method string edit()
 * @method string exists()
 */

class SpamFilter_ResellerAPI_Action
{
    /**
     * @var string $_controller API module
     */
    private $_controller;

    /**
     * @var string $_username API Username
     */
    private $_username;

    /**
     * @var string $_password API Password
     */
    private $_password;

    /**
     * @var string $_hostname API Hostname
     */
    private $_hostname;

    /**
     * @var bool $_sslenabled Use SSL for API calls
     */
    private $_sslenabled;

    /** @var SpamFilter_Controller_Action_Helper_FlashMessenger */
    private $_messageQueue;

    private $_api_access_allowed = true;

    /**
     * Constructor
     *
     * @access public
     *
     * @param string $controller Controller to work with.
     * @param string $messagesQueue.
     *
     * @return SpamFilter_ResellerAPI_Action
     */
    public function __construct($controller, $messagesQueue = null)
    {
        $this->_controller = $controller;
        $config            = Zend_Registry::get('general_config');
        $this->_hostname   = $config->apihost;
        $this->_username   = $config->apiuser;
        $this->_password   = $config->apipass;
        $this->_sslenabled = $config->ssl_enabled;
        if (PHP_SAPI !== 'cli') {
            $this->_messageQueue = $messagesQueue
                ? $messagesQueue : new SpamFilter_Controller_Action_Helper_FlashMessenger;
        }
    }

    /**
     * Caller Magic, actually does the real API communication and return data converting
     *
     * @access public
     *
     * @param string $action
     * @param array  $params
     *
     * @throws RuntimeException
     * @return string
     */
    public function __call($action, $params)
    {
        /** @var $logger SpamFilter_Logger */
        $logger = Zend_Registry::get('logger');

        if (is_array($params)) {
            $params = $params[0]; // 1 array part deeper.
            #Zend_Registry::get('logger')->debug("[ResellerAPI] Requested action: '{$this->_controller} -> {$action}' (params: " . implode(', ', $params) . ")");
        } else {
            #Zend_Registry::get('logger')->debug("[ResellerAPI] Requested action: '{$this->_controller} -> {$action}'");
        }

        // we should make sure the hostname is valid
        $hostnameValidator = new SpamFilter_Validate_Hostname();
        if (!$hostnameValidator->isValid($this->_hostname)) {
            $logger->err("[API] Cannot execute API call because the hostname is invalid: '{$this->_hostname}'");
            return;
        }

        // Lets prepare parameters for method calling
        $encoded_params = array();


        if (!empty($params)) {
            $idn = new IDNA_Convert;
            foreach ($params as $param_name => $param_value) {
                if (in_array($param_name, array('domain'))) {
                    $logger->debug("[API] IDN encoding values for '{$param_name}'");
                    $param_value = $idn->encode($param_value, true);
                }

                if (is_array($param_value)) {
                    $logger->debug("[API] Converting value for '{$param_name}' to an JSON array");
                    // Convert the PHP array to a JSON array.
                    $param_value = Zend_Json::encode($param_value);
                }

                // Put the encoded parameters in to the array we are using for further operations
                $encoded_params[] = rawurlencode(strtolower($param_name)) . '/' . rawurlencode($param_value);
            }
        }

        // Composing URL to request
        $protocol = ((0 < $this->_sslenabled) ? 'https://' : 'http://');
        $url = "{$protocol}{$this->_hostname}/api/{$this->_controller}/{$action}/format/json/" . implode('/', $encoded_params);

        // we shouldn't call other API's for a page in case we get an API_IP_ACCESS_ERROR
        if (!$this->_api_access_allowed) return ;

        // we shouldn't call other API's for a page in case we get an API_IP_ACCESS_ERROR
//        if (!$this->_api_access_allowed) return ;

        // Start API request
        $logger->debug('[API] Ready to call "' . $url . '"');

        $response = $this->_httpRequest($url);

        if ($response === false) // No good response given (404? 500? somekind of error?)
        {
            // Data failed, we received no data / false
            $logger->err('[API] API request failure (FALSE value returned). Response: ' . serialize($response));
            $errors['status']     = false;
            $errors['reason']     = "API_REQUEST_FAILED";
            $errors['additional'] = null;

            return $errors;
        } else {
            if ((stristr($response, ": SQLSTATE")) || (stristr($response, "fatalerror.php"))) {
                $logger->err("[API] API call returned a fatal error!");
                throw new RuntimeException("API communication failed.");
            }

            // Data received
            $logger->debug(
                "[API] API call was executed. Returned data: \n" . (
                150 < strlen($response) ? substr($response, 0, 150) . ' ... (' . strlen($response) . ' bytes)'
                    : $response)
            );

            //@TODO: Implement proper error handling based on (#10370)
            try {
                $data = Zend_Json::decode($response);
            } catch (Exception $e) {
                $logger->err("[API] No JSON response provided, unable to parse and proceed!");
                throw new RuntimeException("Unable to process API response, not in expected format.");
            }

            if (!is_array($data)) {
                // Data convert failed, probably dit not get JSON fed
                $logger->err('[API] API request failure (Unexpected data received). Response: ' . serialize($response));
                $errors['status']     = false;
                $errors['reason']     = "API_REQUEST_FAILED";
                $errors['additional'] = null;

                return $errors;
            }

            // From this point on we can assume that $data is a multidimensional array containing the information we need.

            //Check available limit
            if (isset($data['result']['freelimit'])) {
                return array('result' => $data['result']);
            }

            //It needs for getting info when domains are migrating
            if (isset($data['result']['moved']) || isset($data['result']['notmoved'])
                || isset($data['result']['rejected'])
            ) {
                return array('status' => true, 'result' => $data['result']);
            }


            //First, some error checking
            if (!empty($data['messages']['error']) && is_array($data['messages']['error'])) {
                // We got an error returned.
                $logger->debug("[API] Call executed with an error");
                $errors['status']     = false;
                $errors['additional'] = $data['messages']['error'];

                $lastErr = ''; //we might want to process the first error that is being encountered

                // Provide additional reasons, we have to do this manually since we need #10504 for this to properly work.
                foreach ($data['messages']['error'] as $errorLine) {
                    $lastErr = $errorLine;

                    if (stristr($errorLine, "Supplied credential is invalid.")) {
                        $logger->info('[API] Supplied credential is invalid.');
                        $errors['reason'] = "INVALID_API_CREDENTIALS";
                        break;
                    } elseif (stristr($errorLine, "already exists")
                        || stristr(
                            $errorLine, "present in the filtering software"
                        )
                    ) {
                        // Domain already exists (creation)
                        $logger->info('[API] This domain already exists.');
                        $errors['reason'] = "DOMAIN_EXISTS";
                        break;
                    } elseif (stristr($errorLine, "you is not owner")
                        || stristr(
                            $errorLine, "does not belong to the reseller"
                        )
                    ) {
                        // Domain already exists (creation)
                        $logger->info('[API] This domain already present in the filtering, but user is not owner.');
                        $errors['reason'] = "ALREADYEXISTS_NOT_OWNER";
                        break;
                    } elseif (stristr($errorLine, "Alias already exists")) {
                        // Domain already exists (creation)
                        $logger->info('[API] This alias already exists.');
                        $errors['reason'] = "ALIAS_EXISTS";
                        break;
                    } elseif (stristr($errorLine, "is not registered on")) {
                        // Domain does not exist (authticket)
                        $logger->err('[API] Domain does not exist');
                        $errors['reason'] = "DOMAIN_NOT_EXISTS";
                        break;
                    } elseif (stristr($errorLine, "no permissions")) {
                        // No permission (e.g. domain list)
                        $logger->err('[API] No permissions to execute requested call');
                        $errors['reason'] = "NO_PERMISSION";
                        break;
                    } elseif (stristr($errorLine, "Incorrect usage of API")) {
                        // API translations (Spampanel -> Software) apparently did not work. Wrongly provided variables maybe?
                        $logger->err('[API] API used incorrectly');
                        $errors['reason'] = "API_CORE_ERROR";
                        break;
                    } elseif (stristr($errorLine, "reached the allowed limit")) {
                        $logger->err('[API] Domain Limit reached');
                        $errors['reason'] = "DOMAIN_LIMIT_REACHED";
                        break;
                    } elseif (stristr($errorLine, "No such domain")) {
                        $errors['status'] = true; // if domain deleted on spampanel do not throw error
                        $logger->err('[API] No such domain');
                        $errors['reason'] = "NO_SUCH_DOMAIN";
                        break;
                    } elseif (stristr($errorLine, "has status 'inactive'")) {
                        $logger->err('[API] User is inactive');
                        $errors['reason'] = "API_USER_INACTIVE";
                        break;
                    } elseif (stristr($errorLine, "You don't have permissions to access the API.")) {
                        $logger->err('[API] User does not have API access');
                        $errors['reason'] = "API_ACCESS_DISABLED";
                        break;
                    } elseif (stristr($errorLine, "cannot be a destination")) {
                        $logger->err('[API] Wrong destination hostname(s) causing email loop');
                        $errors['reason'] = "WRONG_DESTINATION_GIVEN";
                        break;
                    } elseif (stristr($errorLine, "no access to this API, current IP address")) {
                        $logger->err('[API] The IP is blocked from accessing the API');
                        $errors['reason'] = "API_IP_ACCESS_ERROR";
                        break;
                    } else {
                        // Generic error.
                        $logger->err('[API] Unknown error');
                        $errors['reason'] = "API_UNHANDLED_ERROR";
                        break;
                    }

                }

                if ($errors['reason'] == "INVALID_API_CREDENTIALS") {
                    $message = array(
                        'message' => "Access to the Spamfilter API is currently disabled due to invalid credentials. You still may run some operations via this add-on but it's strictly prohibited as it can potentially lead to out-of-sync or even broken data. Please check the configuration and if the problem persists contact your administrator or service provider.",
                        'status' => 'error'
                    );
                    if ($this->_messageQueue) {
                        $this->_messageQueue->addMessage($message);
                    }
                }

                if ($errors['reason'] == "API_USER_INACTIVE") {
                    $message = array(
                        'message' => "Access to the Spamfilter API is currently disabled because your account is deactivated. Please check the configuration and if the problem persists contact your administrator or service provider.",
                        'status' => 'error'
                    );
                    if ($this->_messageQueue) {
                        $this->_messageQueue->addMessage($message);
                    }
                }

                if ($errors['reason'] == "API_ACCESS_DISABLED") {
                    $message = array(
                        'message' => "You don't have permissions to access the Spamfilter API. Please email your system administrator about upgrading your account.",
                        'status' => 'error'
                    );
                    if ($this->_messageQueue) {
                        $this->_messageQueue->addMessage($message);
                    }
                }

                if ($errors['reason'] == "API_IP_ACCESS_ERROR" && $this->_api_access_allowed) {
                    $this->_api_access_allowed = false;
                    preg_match('/IP address \'(.+)\' is/', $lastErr, $accessingIp);

                    $message = array(
                        'message' => "At the moment access to the Spamfilter API is disabled by the Spamfilter administrator - your IP address - \"{$accessingIp[1]}\" - is not allowed to make requests. You still may run some operations via this add-on but it's strictly prohibited as it can potentially lead to out-of-sync or even broken data. Please contact your administrator or service provider.",
                        'status' => 'error'
                    );
                    if ($this->_messageQueue) {
                        $this->_messageQueue->addMessage($message);
                    }

                }

                // Request failed, returning error-array.
                return $errors;
            }

            // get response if not null and have no messages
            if (!empty($data['result'])) {
                $logger->debug(
                    "[API] . Response: " . (is_array($data['result'])) ? @implode(',', $data['result']) : $data['result']
                );

                return $data['result'];
            }

            if (($this->_controller == 'authticket') || (in_array($action, array('get', 'list')))) {

                // Single entries can be converted to a string instead (e.g. version retrieval)
                if (count($data['result']) == 1) {
                    $logger->debug("[API] Single array item, converting to string instead. ");

                    return $data['result'];
                }

                // Return the array data.
                $logger->debug("[API] Returning multi-array item. ");

                return $data['result'];
            } else {
                // so. This is a SET command that should give us a YES or NO whether it worked or not.
                // We do not care about the data in this case so lets just ignore it shall we?
                $logger->debug("[API] Requested a SET command. Only need to parse the output for errors. ");
            }

            $logger->debug("[API] Call executed successfully");
            $errors['status'] = true;
            $errors['reason'] = "OK";

            return $errors;
        }
    }

    /**
     * Internal method for running http request
     *
     * @access private
     *
     * @param string $url
     *
     * @return string
     */
    private function _httpRequest($url)
    {
        $config          = new stdClass();
        $config->apiuser = $this->_username;
        $config->apipass = $this->_password;

        return SpamFilter_HTTP::getContent($url, $config);
    }

}
