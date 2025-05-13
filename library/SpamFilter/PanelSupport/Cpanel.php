<?php

/**
 *************************************************************************
 *                                                                       *
 * ProSpamFilter                                                         *
 * Bridge between Webhosting panels & SpamExperts filtering                *
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
 *
 * @category  SpamExperts
 * @package   ProSpamFilter
 * @author    $Author$
 * @copyright Copyright (c) 2011, SpamExperts B.V., All rights Reserved. (http://www.spamexperts.com)
 * @license   Closed Source
 * @version   3.0
 * @link      https://my.spamexperts.com/kb/34/Addons
 * @since     3.0
 */
class SpamFilter_PanelSupport_Cpanel
{
    public const PANEL_FILESYSTEM_LOCATION = "/usr/local/cpanel/";

    /**
     * @access public
     * @var Cpanel_PublicAPI $_api
     */
    public $_api;

    /**
     * @access protected
     * @var SpamFilter_Logger $_logger object
     */
    protected $_logger;

    /**
     * @param $_config object
     */
    public $_config;

    /**
     * @param $_options object
     */
    public $_options;

    public static function getHooksList()
    {
        return array(
            array(
                'category' => 'Whostmgr',
                'event' => 'Accounts::Create',
                'stage' => 'post',
                'action' => ''
            ),
            array(
                'category' => 'Whostmgr',
                'event' => 'Accounts::Remove',
                'stage' => 'pre',
                'action' => ''
            ),
            array(
                'category' => 'Whostmgr',
                'event' => 'Accounts::Modify',
                'stage' => 'post',
                'action' => ''
            ),
            array(
                'category' => 'Whostmgr',
                'event' => 'Domain::park',
                'stage' => 'post',
                'blocking' => 1
            ),
            array(
                'category' => 'Whostmgr',
                'event' => 'Domain::unpark',
                'stage' => 'pre',
                'blocking' => 1
            ),
            array('category' => 'PkgAcct',
                'event' => 'Restore',
                'stage' => 'post',
                'action' => ''
            ),
            array(
                'category' => 'Cpanel',
                'event' => 'Api2::AddonDomain::addaddondomain',
                'stage' => 'post',
                'action' => ''
            ),
            array(
                'category' => 'Cpanel',
                'event' => 'Api2::AddonDomain::deladdondomain',
                'stage' => 'pre',
                'action' => ''
            ),
            array(
                'category' => 'Cpanel',
                'event' => 'Api2::SubDomain::addsubdomain',
                'stage' => 'post',
                'action' => '',
                'escalateprivs' => 1
            ),
            array(
                'category' => 'Cpanel',
                'event' => 'Api2::SubDomain::delsubdomain',
                'stage' => 'pre',
                'action' => '',
                'escalateprivs' => 1
            ),
            array(
                'category' => 'Cpanel',
                'event' => 'Api2::CustInfo::savecontactinfo',
                'stage' => 'post',
                'action' => ''
            ),
            array(
                'category' => 'Cpanel',
                'event' => 'Api2::Email::setmxcheck',
                'stage' => 'post',
                'action' => ''
            )
        );
    }

    /**
     * Verifies whether is possible to use this class on this server and sets up API communication.
     *
     * @param array $options
     *
     * @return SpamFilter_PanelSupport_Cpanel
     * @throws Exception in case it is not possible
     *
     * @see    SpamFilter_Configuration
     *
     * @access public
     */
    public function __construct($options = array())
    {
        $this->_logger = Zend_Registry::get('logger');
        if (!file_exists(self::PANEL_FILESYSTEM_LOCATION)) {
            $this->_logger->crit("Wrong Panelsupport library loaded. This is not cPanel.");
            throw new Exception("Wrong Panelsupport library loaded");
        }

        $this->_options = $options;

        if (isset($options['altconfig'])) {
            $this->_logger->info("Loading alternative configuration.");
            $configurator = new SpamFilter_Configuration($this->_options['altconfig']);
        } else {
            $this->_logger->info("Loading default configuration.");
            $configurator = new SpamFilter_Configuration(CFG_PATH . '/settings.conf');
        }

        if (!Zend_Registry::isRegistered('general_config')) {
            $this->_logger->crit("Config not loaded, please check the configuration file");
            $this->_options['skipapi'] = true; // Skip api at this point
        }

        if (isset($this->_options['skipapi']) && ($this->_options['skipapi'])) {
            $this->_logger->info("Skipping API initialization.");
            $this->_api = null;

            return true;
        }

        $this->_config = Zend_Registry::get('general_config');
        if (is_readable('/root/.accesstoken')) {
            $this->_logger->debug("Using file to obtain access token");
            $hash = trim(file_get_contents('/root/.accesstoken'));
        } else {
            $this->_logger->debug("Using binary to obtain access hash");
            $hash = $configurator->getPassword();
        }

        if ((!$hash) || (!isset($hash)) || (empty($hash)) || (strlen($hash) < 5)) {
            $this->_logger->crit("Unable to authenticate to the API with a missing password: please check if your token exists under 'Manage API Tokens' and in /root/.accesstoken file");

            return false;
        }

        // Include the autoloader
        // require_once('Cpanel/Util/Autoload.php');

        $this->_logger->debug("Initializing cPanel PublicAPI..");
        // Make a configuration data array
        $whmconfig = array(
            'service' => array(
                'whm' => array(
                    'config' => array(
                        'user' => 'root',
                        'hash' => $hash,
                    ),
                ),
            ),
        );

        // Instantiate the PublicAPI client
        try {
            $this->_logger->debug("Creating instance...");
            $this->_api = ((isset($options['cpanel_api_instance'])
                && ($options['cpanel_api_instance'] instanceof Cpanel_PublicAPI))
                ? $options['cpanel_api_instance'] : Cpanel_PublicAPI::getInstance($whmconfig));
        } catch (exception $e) {
            $this->_logger->crit("cPanel API failure: " . $e->getMessage());

            return false;
        }

        $this->_logger->debug("API has been initialized succesfully.");

        return true;
    }

    /**
     * Override the API with a new Cpanel_PublicAPI instance
     *
     * @param Cpanel_PublicAPI $api API to use
     *
     * @return bool status
     *
     * @access public
     * @unused
     * @see    Cpanel_PublicAPI::getInstance()
     */
    public function setApi(Cpanel_PublicAPI $api)
    {
        $this->_api = $api;

        return $this;
    }

    /**
     * Retrieve the API object (instanceof Cpanel_PublicAPI)
     *
     * @return Cpanel_PublicAPI
     *
     * @access public
     * @unused
     * @see    Cpanel_PublicAPI::getInstance()
     */
    public function getApi()
    {
        return $this->_api;
    }

    /**
     * Checks whether the API is available
     *
     *
     * @return bool Status
     *
     * @access public
     */
    public function apiAvailable()
    {
        $this->_logger->debug("Checking if API is available..");

        // Check if the API is available
        if (!$this->_api || !$this->testApi()) {
            $this->_logger->crit("API Unavailable. ");

            return false;
        }

        $v = $this->getVersion();

        if ($v === false || (!isset($v))) {
            $this->_logger->crit("API unavailable. ");

            return false;
        }

        $this->_logger->debug("API Available. ");

        return true;
    }

    /**
     * Get the version of the used control panel
     *
     * @return string|bool Version|Status failed
     *
     * @access   public
     * @requires Cpanel_PublicAPI
     */
    public function getVersion()
    {
        $version = $this->testApi();

        if (!$version) {
            // Fallback method required, API not available?
            $string = trim(shell_exec("/usr/local/cpanel/cpanel -V | awk {'print $1'}"));
            $this->_logger->debug("Fallback versioncheck received versionstring: '{$string}'.");

            if (!empty($string)) {
                $this->_logger->debug("Fallback versioncheck: '{$string}'.");

                return $string;
            }

            $this->_logger->err("Version retrieval failed.");
        }

        return $version;
    }

    public function testApi()
    {
        if (isset($this->_api)) {
            // Make a Whostmgr query
            /** @var $response stdClass */
            try {
                $response = $this->_api->whm_api('version');
                if (isset($response) && $response->version) {
                    // Print result string
                    $this->_logger->debug("Returning API received version: '{$response->version}'.");
                    return $response->version;
                }
            } catch (Exception $e) {
                $this->_logger->crit("Exception caught in " . __METHOD__ . " method : " . $e->getMessage());
            }
        }

        return false;
    }

    /**
     * Check whether the minimum version is met
     *
     * @return bool True/False
     *
     * @access public
     * @see    getVersion()
     */
    public function minVerCheck()
    {
        $this->_logger->debug("Doing version check.");
        $ver = $this->getVersion();

        if (!empty($ver)) {
            $this->_logger->debug("Checking if '$ver' matches...");
            if (version_compare($ver, '11.28', '>=') == 1) {
                $this->_logger->debug("Version OK ($ver)!.");

                return true;
            }
        } else {
            $this->_logger->debug("Current version number is empty.");

            return false;
        }
        $this->_logger->err("Version not OK! (is: '{$ver}').");

        return false;
    }

    /**
     * Setup the DNS for the provided domain
     *
     * @param $data array containing data to use
     *
     * @return bool Status
     *
     * @access public
     * @see    removeMXRecords()
     * @see    AddRecord()
     */
    public function SetupDNS($data)
    {
        $domain = $data['domain'];
        if (!isset($domain)) {
            $this->_logger->err("Request to provision DNS but no domain supplied");

            return false;
        }
        $this->_logger->debug("DNS setup requested for '{$domain}'");

        $records = $data['records'];
        if (!isset($records)) {
            $this->_logger->err("Request to provision DNS for '{$domain}' but no records supplied");

            return false;
        }

        // Remove all current MX records
        $this->removeMXRecords($domain);

        // Setup DNS for $domain using array $records (key = priority);
        foreach ($records as $prio => $value) {
            if (!empty($value)) {
                $this->addMxRecord($domain, $prio, $value);
            } else {
                $this->_logger->debug("Skipping one DNS record because of empty value.");
            }
            unset($zoneRecord);
        }

        /**
         * The domain must be "local" to avoid email processing, but only when domain is protected
         *
         * @see https://trac.spamexperts.com/ticket/18108
         */
        if (empty($data['unprotect'])) {
            $config = Zend_Registry::get('general_config');
            if (0 < $config->bulk_change_routing) {
                $this->SwitchMXmode(
                    array(
                        'domain' => $domain,
                        'mode' => 'local',
                    )
                );
            }
        }

        return true;
    }

    /**
     * Get SPF Record for specified domain
     *
     * @param array $params
     *
     * @return array $spfrecord
     *
     * @access public
     */
    private function getSPFRecord($params)
    {
        $result = $this->_api->getWhm()->makeQuery('dumpzone', $params);
        $array = $result->getResponse('array');
        $spfrecord = [];
        if ($array['metadata']['result']) {
            foreach ($array['data']['zone'] as $zone) {
                foreach ($zone['record'] as $record) {
                    if (isset($record['txtdata'])
                        && substr($record['txtdata'], 0, 5) == 'v=spf'
                        && $record['name'] === ($params['domain'] . '.')
                    ) {
                        $spfrecord = $record;
                    }
                }
            }
        }
        return $spfrecord;
    }

    /**
     * Setup SPF Record for specified domain
     *
     * @param array $params
     *
     * @return bool Status
     *
     * @access public
     */
    public function SetupSPF($params)
    {
        if (empty($this->_config->spf_record)) {
            $this->_logger->debug("Cannot add SPF Record. SPF Record is not set in settings.");
            return false;
        }

        $idn = new IDNA_Convert();
        $args['domain'] = $idn->encode($params['domain']);
        $args['api.version'] = '1';
        $spfrecord = $this->getSPFRecord($args);
        $response = array();
        // SPF Record found in DNS
        if (isset($spfrecord['txtdata']) && !empty($spfrecord['txtdata'])) {
            $args['line'] = $spfrecord['Line'];
            $args['name'] = $spfrecord['name'];
            $args['class'] = $spfrecord['class'];
            $args['ttl'] = $spfrecord['ttl'];
            $args['type'] = $spfrecord['type'];
            $args['txtdata'] = $this->_config->spf_record;
            $response = $this->_api->getWhm()->makeQuery('editzonerecord', $args)->getResponse('array'); //check status
        } else {
            //No existing spf so we create new one
            $args['name'] = $idn->encode($args['domain']) . ".";
            $args['class'] = 'IN';
            $args['ttl'] = '14400';
            $args['type'] = 'TXT';
            $args['txtdata'] = $this->_config->spf_record;
            $response = $this->_api->getWhm()->makeQuery('addzonerecord', $args)->getResponse('array');//check status
        }
        return ($response['metadata']['result'] == 1) ? true : false;
    }

    /**
     * Remove SPF Record for specified domain
     *
     * @param string $domain - domain name
     *
     * @return bool Status
     *
     * @access public
     */
    public function RemoveSPF($domain)
    {
        $idn = new IDNA_Convert();
        $args['domain'] = $idn->encode($domain);
        $args['api.version'] = '1';
        $spfrecord = $this->getSPFRecord($args);
        if (isset($spfrecord['txtdata']) && $spfrecord['txtdata'] == $this->_config->spf_record) {
            $args['zone'] = $domain;
            $args['line'] = $spfrecord['Line'];
            $response = $this->_api->getWhm()->makeQuery('removezonerecord', $args)->getResponse('array');//check status
            return ($response['metadata']['result'] == 1) ? true : false;
        }
        return false;
    }

    /**
     * Switches MX mode to a different mode.
     *
     * @param array $params
     *
     * @return bool Status
     *
     * @access private
     * @bug    The result content is not returning actual status due to cPanel publicAPI limitation.
     */
    public function SwitchMXmode($params)
    {
        if (empty($params['domain'])) {
            $this->_logger->debug("Domain can't be empty.");
            return false;
        }
        $domain = $params['domain'];

        $mode = (empty($params['mode'])) ? 'local' : $params['mode'];

        if (empty($params['user'])) {
            // If the user is not given, we have to look it up ourselves.
            // Please note: The user *must* be the one assigned to the domain else the call will not provide data.
            $user = $this->getDomainUser($domain);
        } else {
            $user = $params['user'];
        }

        $idn = new IDNA_Convert();
        $encodedDomain = $idn->encode($domain);

        if ($user !== false) {
            $this->_logger->debug("Switching MX mode for '{$domain}' to '{$mode}'..");
            try {
                $response = $this->_api->getWhm()
                    ->api2_query($user, 'Email', 'setalwaysaccept', array('domain' => $encodedDomain, 'mxcheck' => $mode));
                $arr = $response->getResponse('array');
            } catch (Exception $e) {
                $this->_logger->crit("Exception caught in " . __METHOD__ . " method : " . $e->getMessage());
            }
            $this->_logger->debug("Switching MX mode for '{$domain}' to '{$mode}' resulted in " . serialize($arr));

            // @TODO: Awaiting response on this potential bug (not getting return content)
            // lets just return 'true' for now, hoping it went successfully
            return true;
        }

        $this->_logger->debug("Unable to switch MX mode.");

        return false;
    }

    /**
     * Retrieves current MX mode
     *
     * @param $params [domain, user] Domain to retrieve info from
     *
     * @return string MX Mode
     *
     * @access public
     */
    public function GetMXmode($params)
    {
        if (empty($params['domain'])) {
            $this->_logger->debug("Domain can't be empty.");
            return false;
        }
        $idn = new IDNA_Convert();
        $domain = $params['domain'];
        $encodedDomain = $idn->encode($domain);

        if (empty($params['user'])) {
            // If the user is not given, we have to look it up ourselves.
            // Please note: The user *must* be the one assigned to the domain else the call will not provide data.
            $user = $this->getDomainUser($encodedDomain);
        } else {
            $user = $params['user'];
        }

        if ($user !== false) {
            $this->_logger->debug("Retrieving MX mode for '{$domain}'..");
            try {
                /** @see https://trac.spamexperts.com/ticket/21273#comment:11 */
                if (in_array(strtolower($user), array('root', 'admin'))) {
                    /** @var Cpanel_Query_Object $response */
                    $response = $this->_api->whm_api('domainuserdata', array('domain' => $encodedDomain));
                    $responseData = $response->getResponse('array');

                    if (!empty($responseData['userdata']['user'])) {
                        $user = $responseData['userdata']['user'];
                    }
                }

                $response = $this->_api->getWhm()->api2_query($user, 'Email', 'listmxs', array('domain' => $encodedDomain));
                $arr = $response->getResponse('array');

                $this->_logger->debug("GetMXmode returned: " . serialize($arr));

                if (!isset($arr['cpanelresult']['data']['0']['detected'])) {
                    $this->_logger->err("The API did not report back with required data. Not sure whether this is remote!");

                    return false;
                } else {
                    return strtolower($arr['cpanelresult']['data'][0]['detected']);
                }
            } catch (Exception $e) {
                $this->_logger->crit("Exception caught in " . __METHOD__ . " method : " . $e->getMessage());
            }
        }

        $this->_logger->debug("Unable to get MX mode.");

        return false;
    }

    /**
     * Retrieves all domains capable of sending/receiving email.
     *
     * @param string $user The username to retrieve domains for.
     *
     * @return array Value of destination host
     *
     * @access public
     */
    public function getAllMailDomains($user)
    {
        // Get all domains owned by $user
        try {
            $response = $this->_api->getWhm()->api2_query($user, 'Email', 'listmaildomains');
            $arr = $response->getResponse('array');
        } catch (Exception $e) {
            $this->_logger->crit("Exception caught in " . __METHOD__ . " method : " . $e->getMessage());
        }
        $domains = array();
        foreach ($arr['cpanelresult']['data'] as $data) {
            $domains[] = $data['domain'];
        }

        return $domains;
    }

    /**
     * Retrieves the destination for the provided domain
     *
     * @param string $domain Domain to lookup destination for
     * @param string $source Source used in logging.
     *
     * @return string Value of destination host
     *
     * @access public
     * @see    getMXRecordContent()
     */
    public function getDestination($domain, $source = '')
    {
        if (!empty($source)) {
            $this->_logger->info("GetDestination: Domaintype = '{$source}'");
        }

        if (empty($domain)) {
            $this->_logger->debug("GetDestination: Empty domain provided to lookup.");

            return null;
        }
        $this->_logger->info("Requesting current set destinations for '{$domain}'");

        $config = Zend_Registry::get('general_config');

        if ($config->use_existing_mx) {
            $this->_logger->debug("Requested to use existing MX records from '{$domain}' as route. Retrieving them");
            // Users want to use existing MX records as destinations (routes). Retrieve them!
            $mxr = $this->GetMXRecordContent($domain);
            if (empty($mxr)) {
                $this->_logger->debug("Destination retrieval for domain '{$domain}' has FAILED.");
                $destination = null;

                return $destination;
            }
            $this->_logger->debug("Current MX records for '{$domain}' have been retrieved.");

            // Build me an array with MX records!
            $my_rr[] = $config->mx1;

            if (!empty($config->mx2)) {
                $my_rr[] = $config->mx2;
            }

            if (!empty($config->mx3)) {
                $my_rr[] = $config->mx3;
            }

            if (!empty($config->mx4)) {
                $my_rr[] = $config->mx4;
            }

            $myRRCount = count($my_rr);
            $foundRR = 0;

            // Check if they aren't already pointing to the filter cluster.
            foreach ($mxr as $r) {
                // $r = record
                $this->_logger->debug("MX record value for '{$domain}': '{$r}'");
                if (in_array($r, $my_rr)) {
                    $foundRR++;
                    // Record $r exists in array $my_rr
                }
            }

            $c1 = count($mxr);
            if ($c1 > $myRRCount) {
                $this->_logger->debug("More records found ({$c1}) than configured ({$myRRCount}).");
            }

            if ($foundRR == $myRRCount) {
                // We have found the same records as stated in "my records".
                $this->_logger->debug(
                    "Current set MX records for '{$domain}' seems to be pointing to the filtering cluster already. Falling back. ({$foundRR} vs {$myRRCount})"
                );
                $destination = null;

                return $destination;
            } else {
                $this->_logger->debug(
                    "I found {$foundRR} current records but the ones to set are {$myRRCount}, falling back to server hostname to deliver email on."
                );
            }

            $this->_logger->debug("Merging current MX records for '{$domain}'");
            $destination = implode(',', $mxr); // Glue them with a ,
        } else {
            $this->_logger->debug("GetDestination: Default destination (this server)");
            // Use the default destination (aka: server hostname)
            $destination = null;
        }
        $this->_logger->debug("Returning destination: '{$destination}' for domain '{$domain}'");

        return $destination;
    }

    public function createBulkProtectResponse($domain, $reason, $reasonStatus = "error", $rawResult)
    {
        return array(
            "domain" => $domain,
            "counts" => array(
                "ok" => 0,
                "failed" => 0,
                "normal" => 0,
                "parked" => 0,
                "addon" => 0,
                "subdomain" => 0,
                "skipped" => 1,
                "updated" => 0,
            ),
            "reason" => $reason,
            "reason_status" => $reasonStatus,
            'rawresult' => $rawResult,
            "time_start" => $_SERVER['REQUEST_TIME'],
            "time_execute" => time() - $_SERVER['REQUEST_TIME'],
        );
    }

    /**
     * Protect all domains on the server according to the configuration
     *
     * @param array $params
     *
     * @return array containing result data
     *
     * @access public
     * @see    getLocalAccounts()
     * @see    IsRemoteDomain()
     * @see    _setupProgressBar()
     * @see    SpamFilter_Panel_Account
     * @see    SpamFilter_Hooks
     * @see    SpamFilter_Panel_ProtectWhm
     * @see    getParkedDomains()
     * @see    getAddonDomains()
     */
    public function bulkProtect($params)
    {
        //set current user
        $params['user'] = $this->_config->apiuser;
        $params['password'] = $this->_config->apipass;

        $domain = $params['domain'];
        try {
            $hook = new SpamFilter_Hooks();
            $protect = new SpamFilter_Panel_ProtectWhm($hook, $this->_api);
            $protect->setDomain($domain);

            $accountInstance = new SpamFilter_Panel_Account(
                $params,
                !$this->_config->handle_only_localdomains,
                $this->_api
            );
            $protect->setAccount($accountInstance);

            if (0 < $this->_config->handle_only_localdomains
                && SpamFilter_Hooks::SKIP_REMOTE == $accountInstance->getErrorCode()) {
                return $this->createBulkProtectResponse($domain, "Skipped: Domain is remote", "error", SpamFilter_Hooks::SKIP_REMOTE);
            }

            switch ($params['type']) {
                case 'account':
                case 'domain':
                    $protect->domainProtectHandler($this->getDestination($domain, 'normal domain'), $this);
                    break;

                case 'parked':
                    $protect->parkedDomainProtectHandler($domain, $this->getDestination($domain, 'parked alias'));
                    break;

                case 'addon':
                    $protect->addonDomainProtectHandler($domain, $this->getDestination($domain, 'parked addon'));
                    break;

                case 'subdomain':
                    $protect->subDomainProtectHandler($domain, $this->getDestination($params['owner_domain'], 'subdomain'));
                    break;

                default:
                    break;
            }
        } catch (Exception $e) {
            $this->_logger->crit("Exception caught in " . __METHOD__ . " method : " . $e->getMessage());
        }

        return $protect->getResult();
    }

    /**
     * Retrieve all users on this system
     *
     *
     * @param array $params
     *
     * @access public
     * @return array of users
     *
     * @see    listaccts()
     */
    public function getUsers($params = null)
    {
        $this->_logger->debug("getUsers");
        $output = array();
        $accounts = $this->listaccts($params);
        if (isset($accounts) && (!empty($accounts)) && is_array($accounts) && (count($accounts) > 0)) {
            foreach ($accounts as $account) {
                if (isset($account['user'])) {
                    // User
                    $this->_logger->debug("User is '{$account['user']}'");
                    $output[] = $account['user'];
                }
                if (isset($account['owner'])) {
                    // Reseller
                    $this->_logger->debug(
                        "The user '{$account['user']}' is owned by reseller '{$account['owner']}', adding to the list"
                    );
                    $output[] = $account['user'];
                }
            }
            $this->_logger->debug("Sorting results");

            return array_unique($output);
        }

        return array();
    }

    /**
     * Retrieve all users and their primary domain on this system
     *
     *
     * @param array $params
     *
     * @return array Raw array of users
     *
     * @access public
     * @see    listaccts()
     */
    public function getPrimaryUsers($params = null)
    {
        $this->_logger->debug("getPrimaryUsers");

        $accounts = $this->listaccts($params);
        if (!empty($accounts) && is_array($accounts)) {
            return $accounts['acct'];
        }

        return array();
    }

    /**
     * Retrieve all domains for given username
     *
     * @param $data Array containing username entry
     *
     * @return array of domains for the provided user
     *
     * @access public
     * @see    listaccts()
     */
    public function getUsersDomains($data)
    {
        $username = $data['username'];
        $username = trim($username);
        $this->_logger->debug("Show all domains of reseller '{$username}'");

        $domains_user = $this->getDomains(array('username' => $username, 'level' => 'user'));
        $domains_owner = $this->getDomains(array('username' => $username, 'level' => 'owner'));
        $localDomains = array_merge($domains_user, $domains_owner);

        // Return a unique array, since the OWNER= can also contain USER=
        $unique = $this->getUniqueDomains($localDomains);

        $c = is_array($unique) ? count($unique) : 0;
        $this->_logger->debug("Returning {$c} domains");
        return $unique;
    }

    /**
     * Check whether we the user is allowed
     *
     *
     * @return bool status
     *
     * @access     public
     * @see        permitted()
     * @deprecated Replaced by SpamFilter_ACL
     */
    public function isAllowed()
    {
        $this->_logger->debug("Security Check");

        return $this->permitted('all');
    }

    /**
     * Retrieve email address linked to domain
     *
     * @param $data array with domain value
     *
     * @return string|bool Domain email address or 'false' if not set.
     *
     * @access public
     */
    public function getDomainContact($data)
    {
        $domain = $data['domain'];
        $this->_logger->debug("Requesting domain contact for '{$domain}'");

        $data = $this->listaccts(array('username' => $domain, 'level' => 'domain'));

        $email = (isset($data['acct']['0']['email'])) ? trim($data['acct']['0']['email']) : '';
        if (!empty($email)) {
            if ($email != "*unknown*") {
                // WHM can return "*unknown*" as a contact. Rather had a FALSE... ah well.
                $this->_logger->debug("Email is set, so returning it.");

                return $email;
            }
        }
        $this->_logger->debug("Email is not set / is unknown {$email}.");

        return false;
    }

    /**
     * Lists all local accounts
     *
     * @param array $params 'search' Optional: Apply a search, 'searchby' Optional: Search based on (e.g. user/owner/domain)
     *
     * @return array List of domains
     * @access private
     */
    private function listaccts($params = array())
    {
        $searchby = (!empty($params['username'])) ? $params['username'] : null;
        $search = (!empty($params['level'])) ? $params['level'] : 'owner';

        // Escape values since we want literal results, not partial.
        if ($searchby !== null) {
            $searchby = '^' . preg_quote($searchby) . '$';
        }

        $this->_logger->debug("ListAccts. Search: '{$search}' with value '{$searchby}'.");

        $arr = array();
        try {
            /** @var $response Cpanel_Query_Object */
            if ('^root$' == $searchby && $this->permitted('all')) {
                $response = $this->_api->whm_api('listaccts');
                $arr = $response->getResponse('array');
            } else {
                $response = $this->_api->whm_api('listaccts', array('search' => $search, 'searchby' => $searchby));

                $arr = $response->getResponse('array');

                // look for owner domains too.
                if ($search == 'owner') {
                    $this->_logger->debug("Search for owner '{$searchby}' account data.");
                    $resellerResponse = $this->_api->whm_api('listaccts', array('search' => 'user', 'searchby' => $searchby));
                    $resellerArr = $resellerResponse->getResponse('array');
                    $arr['acct'] = array_merge($arr['acct'], $resellerArr['acct']);
                }

            }

            $this->_logger->info("Data returned: " . serialize($arr));

            if (isset($arr['data']) && isset($arr['data']['reason']) && isset($arr['data']['result'])
                && (!$arr['data']['result'])
            ) {
                $this->_logger->err("Retrieving accounts failed ({$arr['data']['reason']})");

                return false;
            }
        } catch (Exception $e) {
            $this->_logger->crit("Exception caught in " . __METHOD__ . " method : " . $e->getMessage());
        }

        $this->_logger->debug("Retrieval of accounts has been completed");

        // Check if there is just ONE account, since the cPanel return array is as flat as a dime then.
        return (array)$arr;
    }

    /**
     * Retrieve all MX records for the given domain
     *
     * @param string $domain domain to lookup MX records for
     *
     * @return array|boolean
     *
     * @access public
     */
    public function getMxRecords($domain)
    {
        $idn = new IDNA_Convert();
        $args = array(
            'api.version' => 1,
            'domain' => $idn->encode($domain),
        );

        try {
            // Use the makeQuery method, it will simply assume you KNOW that
            // the function is valid on the cPanel/WHM side
            $response = $this->_api->getWhm()->makeQuery('listmxs', $args);

            // Then deal with your response as normal
            $arr = $response->getResponse('array');

            return (!empty($arr['data']['record'])) ? $arr['data']['record'] : false;
        } catch (Exception $e) {
            // Something went awry :(
            $this->_logger->err("Retrieval of MX records for '{$domain}' has failed (" . $e->getMessage() . ")");
        }

        return false;
    }

    /**
     * @param $domain
     * @param $line
     *
     * @return mixed
     */
    public function removeDNSRecord($domain, $line)
    {
        $this->_logger->debug("Removing DNS line '{$line}' for domain '{$domain}' ... ");
        $idn = new IDNA_Convert();
        try {
            /** @var Cpanel_Query_Object $response */
            $response = $this->_api->whm_api('removezonerecord', array('zone' => $idn->encode($domain), 'Line' => $line));
            $arr = $response->getResponse('array');

            $this->_logger->debug(
                "Removing DNS line '{$line}' for domain '{$domain}' resulted into " . print_r($arr, true)
            );

            if (0 < $arr['result']['0']['status']) {
                // Wait for BIND reloaded ("smart delay")
                // @see https://trac.spamexperts.com/software/ticket/14566
                $nowMicrotime = microtime(true);
                usleep(ceil(1000000 * (ceil($nowMicrotime) - $nowMicrotime)));
            }
        } catch (Exception $e) {
            $this->_logger->crit("Exception caught in " . __METHOD__ . " method : " . $e->getMessage());
        }

        return $arr['result']['0']['status'];
    }

    /**
     * Remove all MX records for the given domain
     *
     * @param string $domain Domain to remove MX records for
     *
     * @return bool Status
     *
     * @access private
     * @see    GetMXRecordContent()
     */
    private function removeMXRecords($domain)
    {
        $this->_logger->debug("Removing MX records for '{$domain}'");

        while (
            is_array($records = $this->GetMXRecords($domain))
            && count($records)
        ) {
            $this->_logger->debug("MX records reported:" . serialize($records));
            $record = array_pop($records);
            $this->_logger->debug("Removing record for {$domain} (Line: {$record['Line']})");
            $this->removeDNSRecord($domain, $record['Line']);
        }

        return true;
    }

    /**
     * Retrieve MX record content for a given domain
     *
     * @param string $domain Domain to retrieve MX content from
     *
     * @return array of MX record content
     *
     * @access private
     * @see    getMxRecords()
     */
    public function GetMXRecordContent($domain)
    {
        $this->_logger->debug("Get MX record contents for '{$domain}'");
        $mxrecords = $this->getMxRecords($domain);

        $records = array();
        if (is_array($mxrecords)) {
            foreach ($mxrecords as $mxrecord) {
                // Append the record to the list
                $records[] = $mxrecord['exchange'];
            }
        }

        return $records;
    }

    /**
     * Create DNS record
     *
     * @param string $domain Domain to create record for
     * @param        $zoneRecord Array of values to set
     *
     * @return bool Status
     *
     * @access     private
     * @deprecated replaced by AddMxRecord
     */
    private function AddRecord($domain, $zoneRecord)
    {
        $serialized = serialize($zoneRecord);
        $this->_logger->debug("Add Record to domain '{$domain}' (values: {$serialized})");
        try {
            $response = $this->_api->addzonerecord($domain, $zoneRecord);

            if (!isset($response)) {
                $this->_logger->err("Adding record to '{$domain}' (values: {$serialized}) has failed!");

                return false;
            }
        } catch (Exception $e) {
            $this->_logger->crit("Exception caught in " . __METHOD__ . " method : " . $e->getMessage());
        }

        $this->_logger->debug("Adding record to '{$domain}' (values: {$serialized}) has been completed succesfully!");

        return true;
    }

    /**
     * AddMXRecord
     * Create MX record
     *
     * @param string $domain Domain to create record for
     * @param int $priority Which priority to add a record for
     * @param string $server Which server to set as MX record destination.
     *
     * @return bool Status
     *
     * @access public
     */
    public function addMxRecord($domain, $priority, $server)
    {
        $idn = new IDNA_Convert();
        $encodedDomain = $idn->encode($domain);
        $args = array(
            //'api.version'	=> 1,		// Only when doing savemxs
            //'domain'	=> $domain,		// Only when doing savemxs
            'name' => $encodedDomain . '.',
            // Don't forget the trailing dot, else it will be a subdomain
            'exchange' => $server,
            // Destination server as normal
            'preference' => (int)$priority,
            // Convert to integer. Strings will become '0' instead then.
            'class' => 'IN',
            // Just to be sure
            'type' => 'MX',
            // Only when doing addzonerecord
            'ttl' => (isset($this->_config->default_ttl)) ? $this->_config->default_ttl : 3600,
            // Use set TTL or default in case it is not set.
        );
        try {
            /** @var Cpanel_Query_Object $response */
            $response = $this->_api->whm_api('addzonerecord', array('zone' => $encodedDomain, 'args' => $args));
            $arr = $response->getResponse('array');

            $this->_logger->debug(
                "Creation of MX record ({$priority} - {$server}) for '{$domain}' resulted into " . print_r($arr, true) . ""
            );

            // Wait for BIND reloaded ("smart delay")
            // @see https://trac.spamexperts.com/software/ticket/14566
            if (0 < $arr['result']['0']['status']) {
                $nowMicrotime = microtime(true);
                usleep(ceil(1000000 * (ceil($nowMicrotime) - $nowMicrotime)));
            }

            #return $arr['metadata']['result']; // only for savemxs
            return $arr['result']['0']['status']; // only for addzonerecord
        } catch (Exception $e) {
            // Something went awry :(
            $this->_logger->err("Creation of MX records for '{$domain}' has failed (" . $e->getMessage() . ")");
        }

        return false;
    }

    /**
     * getLocalAccounts
     * Retrieve all local accounts
     *
     *
     * @return array|bool of accounts|status failed
     *
     * @access private
     */
    private function getLocalAccounts()
    {
        $this->_logger->debug("Get local accounts");
        $accounts = $this->listaccts();
        if (isset($accounts['acct']) && is_array($accounts['acct'])) {
            $c = count($accounts['acct']);
            $this->_logger->debug("Returning {$c} local accounts");

            return $accounts['acct'];
        }
        $this->_logger->debug("No accounts to return");

        return false;
    }

    /**
     * getAddonDomains
     * Retrieve all addon domains
     *
     * @param string $username Username to retrieve addons for
     * @param string $filter Filter based on specific data
     *
     * @return array of addon domains
     *
     * @access public
     */
    public function getAddonDomains($username, $filter = null)
    {
        $result = array();

        $filename = "/var/cpanel/userdata/{$username}/main";

        if (!file_exists($filename) || !is_readable($filename)) {
            $this->_logger->warn("Failed to retrieve parked domains for '$username': unable to read '{$filename}'. Retrieving addon domains via API.");

            $params = array(
                'cpanel_jsonapi_func' => 'listaddondomains',
                'cpanel_jsonapi_apiversion' => 2,
                'cpanel_jsonapi_module' => 'Park',
                'user' => $username,
            );
            $response = $this->_api->getWhm()->makeQuery('cpanel', $params);
            $domains = $response->getResponse('array');
            foreach ($domains['cpanelresult']['data'] as $domain) {
                $result[] = array(
                    'domain' => $domain['rootdomain'],
                    'alias' => $domain['domain']
                );
            }

        } else {

            $lines = file($filename, FILE_IGNORE_NEW_LINES + FILE_SKIP_EMPTY_LINES);

            $mainDomain = '';
            foreach ($lines as $line) {
                if (preg_match('~^main_domain: (.+)$~i', $line, $m)) {
                    $mainDomain = trim($m[1]);

                    break;
                }
            }

            $sectionHasStarted = false;
            foreach ($lines as $line) {
                if ($sectionHasStarted) {
                    if (preg_match('~^\s+([^:]+)~i', $line, $m)) {
                        $result[] = array(
                            'domain' => $mainDomain,
                            'alias' => trim($m[1]),
                        );
                    } else {
                        break;
                    }

                    continue;
                }

                $sectionHasStarted = (0 === strpos($line, 'addon_domains:'));
            }
        }

        return (!empty($result) ? $result : false);
    }

    /**
     * getParkedDomains
     * Retrieve all parked domains
     *
     * @param string $username Username to retrieve parked domains for
     * @param string $filter Filter based on specific data
     *
     * @return array of parked domains
     *
     * @access public
     */
    public function getParkedDomains($username)
    {
        $result = array();
        $filename = "/var/cpanel/userdata/{$username}/main";
        if (!file_exists($filename) || !is_readable($filename)) {
            $this->_logger->warn("Failed to retrieve parked domains for '$username': unable to read '{$filename}'. Retrieving parked domains via API.");
            $params = array(
                'cpanel_jsonapi_func' => 'listparkeddomains',
                'cpanel_jsonapi_apiversion' => 2,
                'cpanel_jsonapi_module' => 'Park',
                'user' => $username,
            );
            $response = $this->_api->getWhm()->makeQuery('cpanel', $params);
            $domains = $response->getResponse('array');
            foreach ($domains['cpanelresult']['data'] as $domain) {
                $result[] = array('alias' => $domain['domain']);
            }
        } else {
            $lines = file($filename, FILE_IGNORE_NEW_LINES + FILE_SKIP_EMPTY_LINES);
            $sectionHasStarted = false;
            foreach ($lines as $line) {
                if ($sectionHasStarted) {
                    if (preg_match('~^\s+-\s+(.+)$~i', $line, $m)) {
                        $result[] = array(
                            'alias' => trim($m[1]),
                        );
                    } else {
                        break;
                    }

                    continue;
                }

                $sectionHasStarted = (0 === strpos($line, 'parked_domains:'));
            }
        }
        return (!empty($result) ? $result : false);
    }

    public function getSubDomains($username)
    {
        $result = array();
        $filename = "/var/cpanel/userdata/{$username}/main";
        if (!file_exists($filename) || !is_readable($filename)) {
            $this->_logger->warn("Failed to retrieve parked domains for '$username': unable to read '{$filename}'. Retrieving parked domains via API.");
            $params = array(
                'cpanel_jsonapi_func' => 'listsubdomains',
                'cpanel_jsonapi_apiversion' => 2,
                'cpanel_jsonapi_module' => 'SubDomain',
                'user' => $username,
            );
            $response = $this->_api->getWhm()->makeQuery('cpanel', $params);
            $domains = $response->getResponse('array');
            foreach ($domains['cpanelresult']['data'] as $domain) {
                $result[] = array('alias' => $domain['domain']);
            }
        } else {
            $lines = file($filename, FILE_IGNORE_NEW_LINES + FILE_SKIP_EMPTY_LINES);
            $sectionHasStarted = false;
            foreach ($lines as $line) {
                if ($sectionHasStarted) {
                    if (preg_match('~^\s+-\s+(.+)$~i', $line, $m)) {
                        $result[] = array(
                            'alias' => trim($m[1]),
                        );
                    } else {
                        break;
                    }

                    continue;
                }

                $sectionHasStarted = (0 === strpos($line, 'sub_domains:'));
            }
        }
        return (!empty($result) ? $result : false);
    }

    /**
     * permitted
     * Check whether the current user is permitted to to something
     *
     * @param string $acl Which ACL should be checked
     *
     * @return bool true/false
     * @see    SpamFilter_Core
     *
     * @access private
     */
    private function permitted($acl)
    {
        // Check if ($user) is $acl allowed
        $user = SpamFilter_Core::getUsername();

        if (empty($user)) {
            $this->_logger->debug("Rejecting access to not-logged-in user '{$user}'");

            return false;
        }

        if ($user == "root") {
            $this->_logger->debug("Granting access to '{$user}'");

            return true;
        }

        if (!empty($acl)) {
            if (!file_exists("/var/cpanel/resellers")) {
                $this->_logger->debug("Unable to verify '{$user}', there are no resellers in the system. [FILE NOT EXISTS]");

                return false; // If we return false, this will fail.
            }

            $lines = file("/var/cpanel/resellers");
            if ((count($lines)) == 0) {
                $this->_logger->debug("Unable to verify '{$user}', there are no resellers in the system. [FILE IS EMPTY]");

                return false; // If we return false, this will fail.
            }

            foreach ($lines as $line) {
                if (preg_match("/^$user:/", $line)) {
                    $line = preg_replace("/^$user:/", "", $line);
                    $perms = explode(",", $line);
                    foreach ($perms as $perm) {
                        if ($perm == "all" || $perm == $acl) {
                            $this->_logger->info("[WHM] Granting access to '{$user}' because of perm: '{$perm}'");

                            return true;
                        }
                    }
                }
            }
        }

        $this->_logger->info("Rejecting access to '{$acl}' for {$user}");

        return false;
    }

    /**
     * IsLocalDomain
     * Check whether the domain is a local domain
     *
     * @param string $domain Domain to check
     *
     * @return bool true/false
     *
     * @access     public
     * @deprecated This is not used, so therefor should be removed.
     */
    public function IsLocalDomain($domain)
    {
        $domain = trim($domain);
        $this->_logger->debug("Checking if {$domain} is listed in /etc/localdomains");
        $domains = array_map('trim', (array)file('/etc/localdomains', FILE_IGNORE_NEW_LINES));

        if (is_array($domains) && in_array($domain, $domains)) {
            $this->_logger->debug("Domain {$domain} is listed in /etc/localdomains");

            return true;
        }

        $this->_logger->debug("Domain {$domain} is NOT listed in /etc/localdomains");

        return false;
    }


    /**
     * Internal factory method for progressbar setup
     *
     * @access     protected
     *
     * @param int $total
     *
     * @return Zend_ProgressBar|null
     * @see        is_cli()
     * @see        Zend_ProgressBar
     * @see        Zend_ProgressBar_Adapter_Console
     * @see        SpamFilter_ProgressBar_Adapter_JsPush
     * @deprecated We do not use it at this point
     */
    protected function _setupProgressBar($total)
    {
        /** @var SpamFilter_Logger $logger */
        $logger = Zend_Registry::get('logger');

        try {

            // Check for CLI vs HTTP mode.
            if (function_exists('is_cli') && is_cli()) {
                $adapter = new Zend_ProgressBar_Adapter_Console();
            } else {
                $adapter = new SpamFilter_ProgressBar_Adapter_JsPush(
                    array(
                        'updateMethodName' => 'Zend_ProgressBar_Update',
                        'finishMethodName' => 'Zend_ProgressBar_Finish'
                    )
                );
            }

            $progressBar = new Zend_ProgressBar($adapter, 0, $total);

            $logger->err("Progressbar set for a total of {$total} items.");

            return $progressBar;
        } catch (Exception $e) {
            $logger->err("Initializing progressbar failed:" . $e->getMessage());
        }

        return null;
    }

    /**
     * Retrieve all resellers
     *
     * @access public
     *
     * @param bool $raw
     *
     * @return array
     */
    public function getResellers($raw = false)
    {
        $this->_logger->debug("Retrieving resellers..");
        try {
            /** @var stdClass $response */
            $response = $this->_api->whm_api('listresellers');
            if ($raw) {
                return $response->reseller; //return the raw content without pre-processing.
            }

            $output = array();
            if (isset($response)) {
                $resellers = $response->reseller;
                foreach ($resellers as $reseller) {
                    $this->_logger->debug("Adding '{$reseller}' to the output.");
                    $output[]['username'] = $reseller;
                }
            }
        } catch (Exception $e) {
            $this->_logger->crit("Exception caught in " . __METHOD__ . " method : " . $e->getMessage());
        }
        $this->_logger->debug("Returning array of resellers: " . serialize($output));

        return $output;
    }

    /**
     * Retrieve userlevel
     *
     * @access public
     *
     * @param string $user
     *
     * @return string User type
     *
     * @throws Zend_Controller_Plugin_Exception
     * @throws Zend_Exception
     *
     * @see    permitted
     */
    public function getUserLevel($user = null)
    {
        // @TODO: Detect whether this is cPanel or WHM, because it (obviously) makes a difference in what we do/show.

        /** @var SpamFilter_Logger $logger */
        $logger = Zend_Registry::get('logger');
        $logger->debug("Retrieving userlevel");
        // Returns the userlevel from the current user.
        // For WHM, this can either be reseller or admin.

        if (empty($user)) {
            $user = strtolower(trim(SpamFilter_Core::getUsername()));
        }

        // Easy check!
        if ($this->permitted('all') || $user == 'root') {
            // Since we are either 'root' or have 'all' permissions (which equals root) we are admin.
            $logger->debug("Logged in as either 'admin' or has permission to 'all'");

            return "role_admin";
        }

        //if not root it must be reseller or user
        $response = $this->_api->whm_api('acctcounts', array('reseller' => $user));
        $data = $response->getResponse('array');

        if ($data['result']['0']['status'] == 1) {
            $logger->debug("WHM API returned that specified user is reseller. Returning role_reseller.");
            return 'role_reseller';
        }

        // In any other case we are a reseller, it is WHM remember?
        $logger->debug("No privileges, client port used. Determined as enduser.");

        return "role_enduser";

        //@TODO: Might also be an emailuser, we need some checks to determine that.
    }

    /**
     * Retrieve all domains belonging to a specific user (owner)
     *
     * @static
     *
     * @param array $params should contain 'username' username to retrieve domains for, 'level' by default 'owner'
     *
     * @return array Domains
     * @access public
     */
    public function getAccountDomains($params)
    {
        $accounts = $this->listaccts($params);
        $domains = array();
        if (isset($accounts) && (!empty($accounts)) && is_array($accounts) && (count($accounts) > 0)) {
            $this->_logger->debug("Search resulted in > 0 entries: " . serialize($accounts));
            foreach ($accounts['acct'] as $key => $data) {
                $domains[$key]['domain'] = $data['domain'];
                $domains[$key]['username'] = $data['user'];
            }

            return $domains;
        }

        return false;
    }

    /**
     * Retrieve all domains belonging to a specific user
     *
     * @static
     *
     * @param array $params should contain 'username' username to retrieve domains for, 'level' by default 'owner'
     *
     * @return array Domains
     * @access public
     * @see    getParkedDomains()
     * @see    getAddonDomains()
     * @see
     */
    public function getDomains($params)
    {
        if (!empty($params['username'])) {
            $username = $params['username'];
            $level = (!empty($params['level'])) ? $params['level'] : 'owner';
            $order = (!empty($params['order'])) ? $params['order'] : 'asc';

            $this->_logger->debug("Show all domains of user '{$username}' (level: {$level})");

            $accounts = $this->getAccountDomains($params);
            $domains = array();
            if (isset($accounts) && (!empty($accounts))) {
                $config = Zend_Registry::get('general_config');
                foreach ($accounts as $user) {
                    $domains[] = array(
                        'domain' => $user['domain'],
                        'type' => 'account',
                        'user' => $user['username'],
                    );
                    if ($config->handle_extra_domains) {
                        $parked = $this->getParkedDomains($user['username']);
                        if (!empty($parked) && is_array($parked)) {
                            foreach ($parked as $alias) {
                                $domains[] = array(
                                    'domain' => $alias['alias'],
                                    'type' => 'parked',
                                    'user' => $user['username'],
                                    'owner_domain' => $user['domain'],
                                );
                            }
                        }
                        $addon = $this->getAddonDomains($user['username']);
                        if (!empty($addon) && is_array($addon)) {
                            foreach ($addon as $alias) {
                                $domains[] = array(
                                    'domain' => $alias['alias'],
                                    'type' => 'addon',
                                    'user' => $user['username'],
                                    'owner_domain' => $user['domain'],
                                );
                            }
                        }
                        $subdomain = $this->getSubDomains($user['username']);
                        if (!empty($subdomain) && is_array($subdomain)) {
                            foreach ($subdomain as $alias) {
                                $domains[] = array(
                                    'domain' => $alias['alias'],
                                    'type' => 'subdomain',
                                    'user' => $user['username'],
                                    'owner_domain' => $user['domain'],
                                );
                            }
                        }
                    }
                }
            }
            return $this->getSortedDomains(array('domains' => $domains, 'order' => $order));
        } else {
            $this->_logger->debug("Username should not be empty");

            return array();
        }
    }

    /**
     * validateOwnership
     * Check whether we are allowed to operate on this domain
     *
     * @static
     *
     * @param string $domain Domain to check
     *
     * @return bool true/false
     * @access public
     * @see    SpamFilter_Core::getUsername()
     */
    public function validateOwnership($domain)
    {
        $user = SpamFilter_Core::getUsername();
        $this->_logger->debug("Checking access to '{$domain}' for '{$user}'");
        if (empty($user)) {
            $this->_logger->debug("Rejecting domain access to unset user '{$user}'");

            return false;
        }

        if ($user == "root") {
            $this->_logger->debug("Granting domain access to '{$user}', as this is the main admin.");

            return true;
        }

        $response = $this->_api->getWhm()->makeQuery('domainuserdata', array('domain' => $domain));
        $data = $response->getResponse('array');
        if ($data['userdata']['user'] == $user) {
            $this->_logger->debug("Granting domain access to '{$user}', as this is the user of domain: '$domain'.");

            return true;
        }
        $response = $this->_api->getWhm()->makeQuery('accountsummary', array('user' => $data['userdata']['user']));
        $resData = $response->getResponse('array');

        if ($resData['acct'][0]['owner'] == $user) {
            $this->_logger->debug("Granting domain access to '{$user}', as this is the owner of domain: '$domain'.");

            return true;
        }

        // User / Owner is not match
        return false;


    }

    /**
     * IsRemoteDomain
     * Check whether the domain is a remote domain
     *
     * @param array $params Domain to check
     *
     * @return bool true/false
     * @access public
     *
     * @throws RuntimeException
     * @see    getDomainUser()
     *
     */
    public function IsRemoteDomain($params)
    {
        // use the API to determine MX status, as we might not have permissions to read /etc/remotedomains
        // We use API2 call Email::listmxs to see if the "detected" option is not 'remote' or 'secondary'

        if (empty($params['domain'])) {
            $this->_logger->debug("Domain should not be empty");

            return false;
        } else {
            $domain = $params['domain'];
        }

        if (empty($params['user'])) {
            // If the user is not given, we have to look it up ourselves.
            // Please note: The user *must* be the one assigned to the domain else the call will not provide data.
            $user = $this->getDomainUser($domain);
        } else {
            $user = $params['user'];
        }

        $is_remote = array();

        if ($this->_config->add_extra_alias) {
            $domain_to_check = (!empty($params['owner_domain'])) ? $params['owner_domain'] : $domain;
        } else {
            $domain_to_check = $domain;
        }

        if ($user !== false) {
            $typeMx = $this->GetMXmode(array('domain' => $domain_to_check, 'user' => $user));

            $this->_logger->debug("{$domain}'s MX mode is detected as '{$typeMx}'");

            $is_remote[] = (in_array($typeMx, array("remote", "secondary")) ? 1 : 0);
        } else {
            $this->_logger->debug("{$domain}'s MX mode guessing: the check is skipped due to empty user");
        }

        $is_remote_flag = (array_sum($is_remote) > 0 || count($is_remote) === 0) ? true : false;

        $this->_logger->debug("{$domain} is " . ((!$is_remote_flag) ? "NOT " : "") . "a remote domain");

        return $is_remote_flag;
    }

    /**
     * isInFilter
     * Check whether the domain added to the filter.
     *
     * @param string $domain Domain to check
     *
     * @return bool true/false
     * @access public
     */
    public function isInFilter($domain, $ownerDomain = '')
    {
        if (SpamFilter_Domain::exists($domain)) {
            $this->_logger->debug("[isInFilter] The domain '{$domain}' is in the filter.");

            return true;
        }
        if (!empty($ownerDomain) && SpamFilter_Domain::exists($ownerDomain)) {
            $this->_logger->debug("[isInFilter] Checking for alias of domain '{$ownerDomain}'");
            $SEAPI = new SpamFilter_ResellerAPI();
            $apiResponse = $SEAPI->domainalias()->list(array('domain' => $ownerDomain));
            foreach ($apiResponse as $alias) {
                if ($alias == $domain) {
                    $this->_logger->debug("[isInFilter] The domain '{$domain}' is alias of domain $ownerDomain.");
                    return true;
                }
            }
        }

        $this->_logger->debug("[isInFilter] The domain '{$domain}' is NOT in the filter, or request failed.");

        return false;
    }

    /**
     * Retrieve the username associated with the domainname
     *
     * @param string $domain Domain to check
     *
     * @return string Username
     * @access public
     */
    public function getDomainUser($domain)
    {
        /** A request-level cache for domain -> user relations */
        if (!function_exists('mb_strtolower')) { // if zend.multibyte extensions is off, script will crash when we want to use mb_strtolower
            $toLower = 'strtolower';
        } else {
            $toLower = 'mb_strtolower';
            mb_internal_encoding('UTF-8');
        }
        $domainUsersCache = array();
        if (empty($domainUsersCache)) {
            $data = $this->listaccts();
            if (isset($data['acct']) && is_array($data['acct'])) {
                foreach ($data['acct'] as $entry) {
                    if (isset($entry['domain'], $entry['user'])) {
                        $domainUsersCache[strtolower($entry['domain'])] = $entry['user'];
                    }
                }
            }
        }

        $idn = new IDNA_Convert();
        $domain = strtolower($idn->encode($domain));

        /**
         * Perhaps it isn't an account domain?
         *
         * @see https://trac.spamexperts.com/ticket/17588
         */
        if (!isset($domainUsersCache[$domain])) {
            /** @noinspection PhpUndefinedClassInspection */
            $cacheKey = SpamFilter_Core::getDomainsCacheId();
            /** @noinspection PhpUndefinedClassInspection */
            $domains = SpamFilter_Panel_Cache::get($cacheKey);

            // No cache set, proceed with retrieval
            if (!$domains) {
                /** @noinspection PhpUndefinedClassInspection */
                $domains = $this->getDomains(array('username' => SpamFilter_Core::getUsername(), 'level' => 'owner'));
                // Cache miss, save the data
                /** @noinspection PhpUndefinedClassInspection */
                SpamFilter_Panel_Cache::set($cacheKey, $domains);
            }

            if (is_array($domains)) {
                foreach ($domains as $d) {
                    if ($d['domain'] == $domain && isset($d['owner_domain'])) {
                        $domain = $toLower($d['owner_domain']);

                        break;
                    }
                }
            }
        }

        return (isset($domainUsersCache[$domain]) ? $domainUsersCache[$domain] : false);
    }

    /**
     * Retrieve the reseller associated with the domainname, used for reseller-centric actions
     *
     * @param string $domain Domain to check
     *
     * @return string Username
     * @access public
     * @see    listaccts()
     */
    public function getDomainOwner($domain)
    {
        $data = $this->listaccts(
            array('username' => $domain, 'level' => 'domain')
        ); // An alternative solution is using "domainuserdata"
        $owner = (isset($data['acct']['0']['owner'])) ? trim($data['acct']['0']['owner']) : '';

        if (!empty($owner)) {
            // The username is apparnatly set :-)
            $this->_logger->debug("Username is set, so returning it.");

            return $owner;
        }
        $this->_logger->debug("Username is not set / is unknown.");

        return false;

    }

    /**
     * Sets the configured brand in the panel
     *
     * @param array $aBrand Array of brand data
     *
     * @return bool Status
     *
     * @see    generatePluginConfig()
     *
     * @access public
     */
    public function setBrand($aBrand)
    {
        $this->_logger->info("[Cpanel] Pushing setBrand to plugin config generator.");

        $this->_logger->debug("[Cpanel] setBrand for brandname: {$aBrand['brandname']}");
        $this->_logger->debug("[Cpanel] setBrand for brandicon: {$aBrand['brandicon']}");

        /**
         * Update plugin icon
         *
         * @see https://trac.spamexperts.com/ticket/17790#comment:2
         */
        if (!empty($aBrand['brandicon']) && function_exists('imagecreatefromstring') && function_exists('imagegif')) {
            $png = imagecreatefromstring(base64_decode($aBrand['brandicon']));
            if (false !== $png) {
                imagegif($png, '/usr/local/cpanel/whostmgr/docroot/themes/x/icons/prospamfilter.gif');
                imagedestroy($png);
            }
        }

        return $this->generatePluginConfig($aBrand['brandname'], $aBrand['brandicon']);
    }

    /**
     * generatePluginConfig
     * Generates the plugin file used by cPanel
     *
     * @param string $brandname Brandname to use (optional)
     * @param string $brandicon Brand icon to use (optional)
     *
     * @return bool Status
     *
     * @access public
     * @see    SpamFilter_Brand
     *
     */
    public function generatePluginConfig($brandname = null, $brandicon = null)
    {
        //find old brandincons
        $oldIcons = array_merge(
            glob("/usr/local/cpanel/base/frontend/x3/branding/prospamfilter*"),
            glob("/usr/local/cpanel/base/frontend/paper_lantern/branding/psf*")
        );

        $this->_logger->info("[Cpanel] Generating cpanelplugin");
        $pluginfile = "/usr/local/prospamfilter/frontend/cpanel/cpanel11/prospamfilter.cpanelplugin";

        $branding = new SpamFilter_Brand();
        if (empty($brandname)) {
            // No name provided, generate it.
            $brandname = $branding->getBrandUsed();
        } else {
            $this->_logger->info("[Cpanel] Generating for '{$brandname}'");
        }

        // Process
        if ((isset($brandname)) && (!empty($brandname))) {
            $this->_logger->info("[Cpanel] Generating for '{$brandname}'");

            if ((isset($brandicon)) && (!empty($brandicon))) {
                $this->_logger->info("[Cpanel] Using custom icon");
                $icon_content = $brandicon;
            } else {
                $this->_logger->info("[Cpanel] Using configured/default icon");
                $icon_content = $branding->getBrandIcon();
            }

            $file_content = "#cpanel plugin file 2.0 (for use with /usr/local/cpanel/bin/register_cpanelplugin)\n";
            $file_content .= "version: 2.0\n";
            $file_content .= "name:prospamfilter\n";
            $file_content .= "description:{$brandname}\n";
            $file_content .= "featuremanager:1\n";
            $file_content .= "url:prospamfilter/index.html\n";
            $file_content .= "acontent:\n";
            $file_content .= "onclick:\n";
            $file_content .= "if:\n";
            $file_content .= "itemorder:999\n";
            $file_content .= "itemdesc:{$brandname}\n";
            $file_content .= "group:mail\n";

            // paper_lantern config update
            $paper_brand = array(
                'icon' => "se-logo.png",
                'group_id' => "email",
                'order' => 999,
                'name' => $brandname,
                'type' => "link",
                'id' => "psf",
                'uri' => "prospamfilter/index.html",
                'feature' => "prospamfilter",

            );

            //update icon for the IE
            if (!empty($icon_content) && is_link("/usr/local/prospamfilter/frontend/cpanel/psf")) {
                file_put_contents(
                    "/usr/local/prospamfilter/frontend/cpanel/psf/brandicon.png",
                    base64_decode($icon_content)
                );
            }

            $icon_content = chunk_split($icon_content, 76, "\n");
            $file_content .= "image:{$icon_content}";

            // Write file content
            $this->_logger->debug("[Cpanel] Writing cpanelplugin content to plugin file.");
            $written = file_put_contents($pluginfile, $file_content);

            $this->_logger->debug("[Cpanel] Writing paper_lantern new branding config.");

            // Write plugin file for paper_lantern
            if (!empty($icon_content)) {
                file_put_contents(
                    "/usr/local/prospamfilter/bin/cpanel/paper_lantern/psf_button/" . $paper_brand['icon'],
                    base64_decode($icon_content)
                );
            }
            file_put_contents(
                "/usr/local/prospamfilter/bin/cpanel/paper_lantern/psf_button/install.json",
                '[' . json_encode($paper_brand, 128) . ']'
            );
            //creating new archive containing the new config
            shell_exec("cd /usr/local/prospamfilter/bin/cpanel/paper_lantern/ && tar cfj psf.tar.bz2 psf_button");

            if (!$written) {
                // Failed writing to the file.
            } else {
                // Cleanup old left overs
                $this->_logger->debug("[Cpanel] Checking for leftover images blocking icon generation..");
                foreach ($oldIcons as $filename) {
                    $this->_logger->debug("[Cpanel] Removing leftover: {$filename}.");
                    unlink($filename);
                }

                // Re-register plugin
                $this->_logger->debug("[Cpanel] Re-registering plugin for paper_lantern + jupiter & generating sprites.");

                $buttonConfig = '/usr/local/prospamfilter/bin/cpanel/paper_lantern/psf.tar.bz2';
                if (is_dir('/usr/local/cpanel/base/frontend/paper_lantern')) {
                    shell_exec("/usr/local/cpanel/scripts/install_plugin {$buttonConfig} --theme paper_lantern");
                }
                if (is_dir('/usr/local/cpanel/base/frontend/jupiter')) {
                    shell_exec("/usr/local/cpanel/scripts/install_plugin {$buttonConfig} --theme jupiter");
                }

                $this->_logger->debug("[Cpanel] Re-registering plugin for other themes & generating sprites.");

                // Creating dynamicui directory
                // @see https://trac.spamexperts.com/ticket/22354
                if (is_dir('/usr/local/cpanel/base/frontend/x3')
                    && !is_dir('/usr/local/cpanel/base/frontend/x3/dynamicui')
                ) {
                    mkdir('/usr/local/cpanel/base/frontend/x3/dynamicui');
                }
                $status = shell_exec("/usr/local/cpanel/bin/register_cpanelplugin {$pluginfile}");

                //@see https://trac.spamexperts.com/ticket/17098
                $this->_logger->debug("[Cpanel] Setting up brand name for the side-menu");
                $cgi = '/usr/local/prospamfilter/frontend/whm/prospamfilter.php';
                if (file_exists($cgi)) {
                    shell_exec("sed -e 's/#WHMADDON:prospamfilter:.*/#WHMADDON:prospamfilter:{$brandname}/' -i {$cgi}");
                    $appConfigFile = '/usr/local/prospamfilter/bin/cpanel/appconfig/prospamfilter_whm.conf';
                    shell_exec("/usr/local/cpanel/bin/register_appconfig $appConfigFile");
                } else {
                    $this->_logger->err("[Cpanel] Could not set brand name to sidemenu. {$cgi} doesn't exist");
                }

                if (strstr($status, 'Register Complete')) {
                    $this->_logger->info("[Cpanel] Regenerating cpanelplugin has been completed.");

                    return true;
                }
            }
        }
        $this->_logger->err("[Cpanel] Regenerating cpanelplugin has FAILED");

        return false;
    }

    /**
     * Collection domains getter
     *
     *
     * @param bool $informer
     *
     * @return array of domais
     *
     * @access public
     *
     * @todo   Check if this still works with the new paneldriver
     */
    public function getCollectionDomains($informer = false)
    {
        $collectionDomains = SpamFilter_Panel_Cache::get('collectiondomains');

        if ($informer) {

            /** @var stdClass $sessionManager */
            $sessionManager = new SpamFilter_Session_Namespace();
            $sessionManager->bulkprotectinformer = 'Getting list of domains...';
            $sessionManager->bulkprotectinformerstatus = 'run';
        }

        if (!$collectionDomains) {

            // Get all domains
            $local_accounts = $this->getLocalAccounts();

            if (is_array($local_accounts) && 0 < sizeof($local_accounts)) {
                foreach ($local_accounts as $account) {
                    $accountInstance = new SpamFilter_Panel_Account(
                        $account,
                        !$this->_config->handle_only_localdomains,
                        $this->_api
                    );
                    $user = $accountInstance->getUser();
                    $collectionDomains[] = array(
                        'name' => $accountInstance->getDomain(),
                        'type' => 'domain',
                        'user' => $user
                    );

                    // Additional domains:

                    // Check if we have to handle additional domains
                    if ($this->_config->handle_extra_domains) {
                        // Check if there is a username, we need this to look for addons.
                        if (!empty($user)) {
                            $this->_logger->debug("Handling additional domains for '{$account['user']}'.");

                            /*
                                Add Parked Domains.
                            */
                            if ($informer && isset($sessionManager)) {
                                $sessionManager->bulkprotectinformer = 'Getting list of parked domains...';
                            }
                            $parkedDomains = $this->getParkedDomains($accountInstance->getUser());
                            if (empty($parkedDomains)) {
                                // No results.
                                $this->_logger->debug(
                                    "No parked domains domains to add for '{$account['user']}' ('{$account['domain']}')."
                                );
                            } else {
                                $this->_logger->debug(
                                    "Adding parked domains for '{$account['user']}' ('{$account['domain']}')."
                                );

                                foreach ($parkedDomains as $parked) {
                                    $collectionDomains[] = array(
                                        'name' => $parked['alias'],
                                        'owner_domain' => $account['domain'],
                                        'type' => 'parked',
                                        'user' => $user,
                                    );
                                }
                            }

                            /*
                                Add Addon Domains.
                            */
                            if ($informer && isset($sessionManager)) {
                                $sessionManager->bulkprotectinformer = 'Getting list of addon domains...';
                            }
                            $addonDomains = $this->getAddonDomains($account['user']);
                            if (empty($addonDomains)) {
                                // No results.
                                $this->_logger->debug("No additional domains to add for '{$account['user']}'.");
                            } else {
                                $this->_logger->debug("Adding additional domains for '{$account['user']}'.");
                                foreach ($addonDomains as $addon) {
                                    $collectionDomains[] = array(
                                        'name' => $addon['alias'],
                                        'owner_domain' => $account['domain'],
                                        'type' => 'addon',
                                        'user' => $user
                                    );
                                }
                            }

                            /*
                                Add Subomains.
                            */
                            if ($informer && isset($sessionManager)) {
                                $sessionManager->bulkprotectinformer = 'Getting list of subdomains...';
                            }
                            $subDomains = $this->getsubDomains($account['user']);
                            if (empty($subDomains)) {
                                // No results.
                                $this->_logger->debug("No additional subdomains to add for '{$account['user']}'.");
                            } else {
                                $this->_logger->debug("Adding additional subdomains for '{$account['user']}'.");
                                foreach ($subDomains as $sub) {
                                    $collectionDomains[] = array(
                                        'name' => $sub['alias'],
                                        'owner_domain' => $account['domain'],
                                        'type' => 'subdomain',
                                        'user' => $user
                                    );
                                }
                            }
                        } else {
                            $this->_logger->debug("Cannot add subdomains because we don't have a username");
                        }
                    }
                }
                $this->_logger->info("Collection has completed.");
            } else {
                $this->_logger->info("Nothing to protect.");
                if ($informer && isset($sessionManager)) {
                    $sessionManager->bulkprotectinformer = 'Nothing to protect.';
                }
            }

            if (!$collectionDomains) {
                $collectionDomains = array();
            }

            // Filter duplicates from the result
            // @see https://trac.spamexperts.com/software/ticket/14697
            $collectionDomains = self::multidimArrayUnique($collectionDomains, 'name');

            // Refresh domains cache
            SpamFilter_Panel_Cache::set('collectiondomains', $collectionDomains);
        }

        if ($informer && isset($sessionManager)) {
            $sessionManager->bulkprotectinformerstatus = 'protecting';
            if (is_array($collectionDomains) && 0 < count($collectionDomains)) {
                $sessionManager->bulkprotectinformer
                    = 'List of domains has completed. Starting the process of protecting...';
            }
        }

        $order = SpamFilter_Panel_Cache::get('domains_sort_order');
        $order = (!empty($order)) ? $order : 'asc';

        return $this->getSortedDomains(
            array('domains' => $collectionDomains, 'order' => $order, 'forbulkprotect' => true)
        );
    }

    /**
     * Retrieve all server IP's
     *
     * @return array of IP addresses
     *
     * @access public
     */
    public function getIpAddresses()
    {
        $args = array(
            'api.version' => 1,
        );
        try {
            $response = $this->_api->getWhm()->makeQuery('listips', $args);
            $arr = $response->getResponse('array');
            $ips = array();
            if (isset($arr) && is_array($arr) && isset($arr['data']) && isset($arr['data']['ip'])) {
                foreach ($arr['data']['ip'] as $ipset) {
                    $this->_logger->debug("Adding IP {$ipset['ip']} to the list of IP's to return");
                    $ips[] = $ipset['ip'];
                }
            }

            $this->_logger->debug("Returning ips:" . serialize($ips));

            return $ips;
        } catch (Exception $e) {
            // Something went awry :(
            $this->_logger->err("Retrieval of IP addresses has failed (" . $e->getMessage() . ")");
        }

        return false;
    }

    /**
     * Migrate all domains to a diffent user
     *
     * @param $params Array of parameters
     *
     * @return bool status
     *
     * @access public
     */
    public function migrateDomainsTo($params)
    {
        if (!is_array($params)) {
            $this->_logger->err("Unable to migrate due to incorrect request.");

            return false;
        }

        if (!isset($params['username'])) {
            $this->_logger->err("Unable to migrate due to missing username.");

            return false;
        }

        if (!isset($params['password'])) {
            $this->_logger->err("Unable to migrate due to missing password.");

            return false;
        }

        $username = $params['username'];
        $password = $params['password'];
        $this->_logger->info("Going to migrate all domains to {$username}.");

        // Retrieve all domains
        $collectionDomains = $this->getCollectionDomains();
        $domains = array();
        foreach ($collectionDomains as $collectionDomain) {
            $domains[] = $collectionDomain['name'];
        }

        $countDomains = count($domains);
        if ($countDomains > 0) {
            $this->_logger->info("Starting migration.");
            $hook = new SpamFilter_Hooks();
            $freelimit = $hook->GetFreeLimit($username, $password);
            $is_success = false;
            if ('unlimit' == $freelimit || $freelimit - $countDomains >= 0) {
                $limitdomain = null;
                $notmoved = array();
                $moved = array();
                $rejected = array();
                while (!empty($domains)) {
                    $domain_chunk = array_splice($domains, 0, 25);
                    $response = $hook->MigrateDomain($domain_chunk, $username, $password);
                    $limitdomain = (!empty($response['result']['limit']) && is_null($limitdomain))
                        ? $response['result']['limit'] : 0;
                    if (!empty($response['result']['notmoved'])) {
                        $notmoved = array_merge($notmoved, (array)$response['result']['notmoved']);
                    }
                    if (!empty($response['result']['rejected'])) {
                        $rejected = array_merge($rejected, (array)$response['result']['rejected']);
                    }
                    if (!empty($response['result']['moved'])) {
                        $moved = array_merge($moved, (array)$response['result']['moved']);
                    }
                }
                $this->_logger->info("Migration completed.");
                $messages = array();
                if (count($notmoved) > 0 || count($rejected) > 0) {
                    if (count($notmoved) > 0) {
                        $messages[] = array(
                            'message' => 'Domains have not been migrated: ' . join(', ', $notmoved),
                            'status' => 'error'
                        );
                    }
                    if (count($rejected) > 0) {
                        $messages[] = array(
                            'message' => "You don't own some of the listed domains so they aren't moved: " . join(
                                ', ',
                                $rejected
                            ),
                            'status' => 'error'
                        );
                    }
                    if (count($moved) > 0) {
                        $messages[] = array(
                            'message' => "Domains have been migrated: " . join(', ', $moved),
                            'status' => 'success'
                        );
                        $is_success = true;
                    }
                } else {
                    $messages[] = array('message' => 'Domains have been migrated to new user.', 'status' => 'success');
                    $is_success = true;
                }

                return array('is_success' => $is_success, 'messages' => $messages);
            } else {
                $errMsg = "Migration failed. The new user's domains limit is lower than required for the migration.";
                $this->_logger->err($errMsg);
                $messages[] = array('message' => $errMsg, 'status' => 'error');

                return array('is_success' => $is_success, 'messages' => $messages);
            }
        }
        $this->_logger->err("Migration failed.");

        return false;
    }

    /**
     * Retrieve all unique domains
     *
     * @param array $domains Array containing domain entry
     *
     * @return bool|array Array of unique domains
     *
     * @access public
     */
    public function getUniqueDomains($domains)
    {
        // Return a unique array, since the OWNER= can also contain USER=
        if (is_array($domains)) {
            $unique = $domain_names = array();
            foreach ($domains as $domain) {
                if (!empty($domain['domain']) && !in_array($domain['domain'], $domain_names)) {
                    $unique[] = $domain;
                    $domain_names[] = $domain['domain'];
                }
            }

            return $unique;
        }

        return false;
    }

    /**
     * Retrieve username for the domain from the collection domains list
     *
     * @param $domain string domain name
     *
     * @return string
     *
     * @access public
     */
    public function getUsernameByDomain($domain)
    {
        $username = null;

        $usernamesCacheId = 'domain_users';
        $domains = SpamFilter_Panel_Cache::get($usernamesCacheId);
        if (empty($domains)) {
            $domains = $this->getDomains(
                array(
                    'username' => SpamFilter_Core::getUsername(),
                    'level' => 'owner',
                )
            );
            SpamFilter_Panel_Cache::set($usernamesCacheId, $domains, 1800);
        }

        if (is_array($domains)) {
            foreach ($domains as $d) {
                if ($domain == $d['domain']) {
                    $username = (!empty($d['user']) ? $d['user'] : null);
                    break;
                }
            }
        }

        if (is_null($username)) {
            $owners = SpamFilter_Panel_Cache::get('owners');
            if (is_array($owners) && array_key_exists($domain, $owners)) {
                $username = $owners[$domain];
            }
        }

        return $username;
    }

    /**
     * Retrieve sorted domains list
     *
     * @param $params [domains array, order string ('asc', 'desc'), forbulkprotect boolean]
     *
     * @return array
     *
     * @access public
     */
    public function getSortedDomains($params)
    {
        $domains = (!empty($params['domains'])) ? $params['domains'] : null;
        $order = (!empty($params['order'])) ? $params['order'] : 'asc';
        $forbulkprotect = (!empty($params['forbulkprotect'])) ? $params['forbulkprotect'] : false;

        $domain_name = ($forbulkprotect) ? 'name' : 'domain';
        if (is_array($domains)) {
            $unique = $domain_names = $domains_sorted = array();
            foreach ($domains as $key => $domain) {
                if (!empty($domain[$domain_name]) && !in_array($domain[$domain_name], $domain_names)) {
                    $unique[$domain[$domain_name]] = $key;
                    $domain_names[] = $domain[$domain_name];
                }
            }

            if ('asc' == $order) {
                ksort($unique);
            } else {
                krsort($unique);
            }

            foreach ($unique as $key) {
                $domains_sorted[] = $domains[$key];
            }

            return $domains_sorted;
        }

        return false;
    }

    /**
     * Returns domains matched with filter
     *
     * @param $params [domains array, filter string]
     *
     * @return array
     *
     * @access public
     */
    public function filterDomains($params)
    {
        $filter = $params['filter'];
        $filteredDomains = array();
        foreach ($params['domains'] as $domain) {
            if (strpos($domain['domain'], $filter) !== false) {
                $filteredDomains[] = $domain;
            }
        }
        return $filteredDomains;
    }

    /**
     * Method for array uniqualization. Can be applied for multidimensional
     * arrays only. Does comparison based on string representation on entry
     *
     * @static
     * @access public
     *
     * @param array $array
     * @param string $uniqueField
     *
     * @return array
     */
    final public static function multidimArrayUnique(array $array, $uniqueField)
    {
        $addedValues = $result = array();

        foreach ($array as $entry) {
            if (!empty($entry[$uniqueField]) && empty($addedValues[$entry[$uniqueField]])) {
                $addedValues[$entry[$uniqueField]] = 1;
                $result[] = $entry;
            }
        }

        return $result;
    }

    /**
     * Returns package name assigned to domain
     *
     * @param $domain - domain
     *
     * @return string - package name
     *
     * @access public
     */
    public function getDomainPackage($domain)
    {
        $idn = new IDNA_Convert();
        $args['domain'] = $idn->encode($domain);
        try {
            $response = $this->_api->getWhm()->makeQuery('accountsummary', $args);
            $arr = $response->getResponse('array');
        } catch (Exception $e) {
            $this->_logger->crit("Exception caught in " . __METHOD__ . " method : " . $e->getMessage());
        }
        return $arr['acct'][0]['plan'];
    }

    /**
     * Returns package name assigned to domain
     *
     * @param $domain - domain
     *
     * @return string - package name
     *
     * @access public
     */
    public function getUserPackages($user)
    {
        $args['user'] = $user;
        try {
            $response = $this->_api->getWhm()->makeQuery('accountsummary', $args);
            $arr = $response->getResponse('array');
        } catch (Exception $e) {
            $this->_logger->crit("Exception caught in " . __METHOD__ . " method : " . $e->getMessage());
        }

        return $arr['acct'][0]['plan'];
    }

    /**
     * Returns name of feature list assigned to package
     *
     * @param $package - package
     *
     * @return string - feature list name
     *
     * @access public
     */
    public function getFeatureList($package)
    {
        $args['api.version'] = '1';
        $args['pkg'] = $package;
        try {
            $response = $this->_api->getWhm()->makeQuery('getpkginfo', $args);
            $arr = $response->getResponse('array');
        } catch (Exception $e) {
            $this->_logger->crit("Exception caught in " . __METHOD__ . " method : " . $e->getMessage());
        }
        return $arr['data']['pkg']['FEATURELIST'];
    }

    /**
     * Check if domain has enabled feature
     *
     * @param $domain - domain
     *
     * @return boolean
     *
     * @access public
     */
    public function hasFeatureEnabled($domain)
    {
        $this->_logger->debug(__METHOD__ . " checking for domain '{$domain}'");
        $package = $this->getDomainPackage($domain);
        $args['featurelist'] = $this->getFeatureList($package);
        $args['api.version'] = '1';
        $this->_logger->debug(__METHOD__ . " package for domain '{$domain}' is '{$package}'");
        $this->_logger->debug(__METHOD__ . " feature list for package '{$package}' is '{$args['featurelist']}'");
        $hasFeatureEnabled = false;
        try {
            $response = $this->_api->getWhm()->makeQuery('get_featurelist_data', $args);
            $arr = $response->getResponse('array');
            if ($arr['metadata']['result'] === 1) {
                foreach ($arr['data']['features'] as $feature) {
                    if ($feature['id'] === 'prospamfilter') {
                        if ($feature['value'] === "1" && $feature['is_disabled'] === "0") {
                            $hasFeatureEnabled = true;
                        } else {
                            if ($feature['value'] === "0") {
                                $this->_logger->debug(__METHOD__ . " not included in feature list '{$args['featurelist']}'");
                            }
                            if ($feature['is_disabled'] === "1") {
                                $this->_logger->debug(__METHOD__ . " included in feature list 'disabled'");
                            }
                        }
                        break;
                    }
                }
            }
        } catch (Exception $e) {
            $this->_logger->crit("Exception caught in " . __METHOD__ . " method : " . $e->getMessage());
        }
        $this->_logger->debug(__METHOD__ . " result for domain '{$domain}': " . var_export($hasFeatureEnabled, true));
        return $hasFeatureEnabled;
    }


    /**
     * Check if reseller has enabled feature
     *
     * @param $user
     *
     * @return boolean
     *
     * @access public
     */
    public function resellerHasFeatureEnabled($user)
    {
        $this->_logger->debug("Checking if Prospamfilter feature is available for " . $user);

        $args = array(
            'api.version' => '1',
            'user' => $user,
            'feature' => 'prospamfilter'
        );

        $hasFeature = false;

        try {
            $response = $this->_api->getWhm()->makeQuery('verify_user_has_feature', $args);
            $arr = $response->getResponse('array');
            $hasFeature = $arr['data']['has_feature'];
        } catch (Exception $e) {
            $this->_logger->crit("Exception caught in " . __METHOD__ . " method : " . $e->getMessage());
        }

        $this->_logger->debug("Prospamfilter feature is available result: " . $hasFeature);

        return $hasFeature;
    }

    /**
     * Gather all Installed Hooks
     *
     * @return array
     *
     * @access public
     */
    public function listHooks()
    {
        $json = shell_exec("/usr/local/cpanel/bin/manage_hooks list --output JSON");
        $array = json_decode($json, true);
        if (is_array($array)) {
            return $array;
        } else {
            die("Cannot gather hook list! Aborted!");
        }
    }

    /**
     * Check if hook is already added into panel
     *
     * @hooks - array with data of hooks
     * @file - script file name
     *
     * @return bool
     *
     * @access public
     */
    public function isHookExists(array $hooks, $category, $event, $stage, $file)
    {
        if (!empty($hooks[$category][$event])) {
            foreach ($hooks[$category][$event] as $record) {
                if (strpos($record['hook'], $file) !== false && $record['stage'] == $stage) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Adding or delete hook using manage_hook functionality
     *
     * @params - array with data of hooks
     * @see guide to standarized hooks at : https://documentation.cpanel.net/display/SDK/Guide+to+Standardized+Hooks+-+Hookable+Events
     *
     * @return array
     *
     * @access public
     */
    public function manageHooks($params)
    {
        $registeredHooks = $this->listHooks();
        $file = $params['file'];
        $do = $params['do'];
        if (!is_executable($file)) {
            shell_exec('chmod +x ' . $file);
        }
        foreach ($params['hooks'] as $hook) {
            if ($do == 'add' && $this->isHookExists($registeredHooks, $hook['category'], $hook['event'], $hook['stage'], $file)) {
                echo 'Skipped hook ' . $hook['event'] . ' as it already exists.' . PHP_EOL;
                continue;
            } else {
                $commandStr = "/usr/local/cpanel/bin/manage_hooks " . $do . " script " . $file . " --manual --category " . $hook['category'] . " --event '" . $hook['event'] . "' --stage " . $hook['stage'] . " --action='" . $hook['action'] . "'";
                if (isset($hook['escalateprivs']) && $hook['escalateprivs']) {
                    $commandStr .= " --escalateprivs";

                }

                system($commandStr);
            }
        }
        return "Done." . PHP_EOL;
    }

    /**
     * Returns main domain of user.
     *
     * @param $user - user name
     *
     * @return string - main domain
     *
     * @access public
     */
    public function getMainDomain($user)
    {
        $args['user'] = $user;
        $response = $this->_api->getWhm()->makeQuery('accountsummary', $args);
        $arr = $response->getResponse('array');
        return $arr['acct'][0]['domain'];
    }

    /**
     *
     *
     */
    public function isDisabledForResellers()
    {
        return $this->_config->disable_reseller_access ? true : false;
    }

}
