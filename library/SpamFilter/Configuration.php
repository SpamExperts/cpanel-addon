<?php
/*
*************************************************************************
 *                  	                                              		*
 * ProSpamFilter                                                         *
 * Bridge between Webhosting panels & SpamExperts filtering	     		*
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
 * @since     3.0
 */
/** @noinspection PhpUndefinedClassInspection */
class SpamFilter_Configuration
{
    const ID_IN_REGISTRY = 'general_config';

    protected $_configData;
    var $_configFile;

    /**
     * __construct
     * Handles the basic setup requirements for the Config class
     *
     * @param string $fileName Configuration file (ini format)
     *
     * @throws RuntimeException
     * @return \SpamFilter_Configuration
     *
     * @access public
     */
    public function __construct($fileName)
    {
        if (empty($fileName)) {
            Zend_Registry::get('logger')->err("[Config] No configfile provided.");

            throw new RuntimeException('No configfile provided.');
        }

        Zend_Registry::get('logger')->debug("[Config] Configuration constructed with filename '{$fileName}'");
        //Windows way to gather config data
        if(SpamFilter_Core::isWindows()){
            $this->_fileName = $fileName;
            if(isset($_SESSION['auth']['isAuthenticatedAsRoot']) && $_SESSION['auth']['isAuthenticatedAsRoot'] == true){
                // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.FilesystemFunctions.WarnFilesystem
                if (is_readable($fileName)) {
                    Zend_Registry::get('logger')->debug("[Config] Using getconfig wrapper to obtain configuration");
                    $this->_configData = $this->_getBinaryContent();
                }
            } else { // If not admin account
                Zend_Registry::get('logger')->debug("[Config] Using getconfig wrapper to obtain configuration");
                $this->_configData = $this->_getBinaryContent();
            }                    
        } else {
            // Check if we can read the file, if we can we use the normal featureset
            // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.FilesystemFunctions.WarnFilesystem
            if (is_readable($fileName)) {
                Zend_Registry::get('logger')->debug("[Config] Using normal filename to obtain configuration");
                $this->_configFile = $fileName;
                $this->_configData = $this->_getIniContent($fileName);
            } else { // If we cannot read the file, we should move to a different approach
                Zend_Registry::get('logger')->debug("[Config] Using SUID wrapper to obtain configuration");
                $this->_configData = $this->_getBinaryContent();
            }
        }

        if ((isset($this->_configData)) && (!empty($this->_configData)) && ($this->_configData !== false)) {
            Zend_Registry::get('logger')->debug("[Config] Configuration loaded");
            Zend_Registry::set('general_config', $this->_configData); // Optional, no real need for it though.
        } else {
            if(SpamFilter_Core::isWindows()){
                Zend_Registry::get('logger')->debug("[Config] Unable to obtain conf, initialize empty configuration file.");
                $this->_saveBinaryContent($this->setInitial());
            } else {
                Zend_Registry::get('logger')->err("[Config] Unable to obtain");

                Zend_Registry::set('general_config', new Zend_Config(array()));
            }
        }
    }

    //Concept way to gather config data if exists way to block access to settings for psacln users group
        
//    private function getConfigAsAdmin($fileName){
//        $host = system('hostname');
//        $filecontent = system('runas.exe /user:' . $host . '@psaadm "type '. $fileName . '"');
//        $config = new Zend_Config_Ini($filecontent, true);
//        return $config;       
//    }

    /**
     * _getIniContent
     * Returns the content of the ini file and converts it to a Zend_Config object
     *
     * @param string $fileName Filename of inifile to load
     *
     * @return Zend_Config object
     *
     * @access private
     */
    private function _getIniContent($fileName)
    {
        // Used to obtain the normal ini content
        try {
            Zend_Registry::get('logger')->debug("[Config] Loading config from '{$fileName}'");
            $config = new Zend_Config_Ini($fileName);
        } catch (Zend_Config_Exception $e) {
            Zend_Registry::get('logger')->crit("[Config] Failed to load the INI config. ({$e->getMessage()})");

            return false;
        }

        // Check if it is set.
        if ($config) {
            Zend_Registry::get('logger')->debug("[Config] Data is set, saving to registry");

            return $config;
        }

        Zend_Registry::get('logger')->err("[Config] Loading configuration has failed.");

        return false;
    }

    /**
     * _getBinaryContent
     * Returns the content of the binary and converts it to a Zend_Config object
     *
     * @return Zend_Config object
     *
     * @access private
     */
    private function _getBinaryContent()
    {
        // Used for binary content, that has to be converted to an INI
        $binary = SpamFilter_Core::getConfigBinary();

        // Check if it is executable
        // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.FilesystemFunctions.WarnFilesystem
        if (!is_executable($binary)) {
            Zend_Registry::get('logger')->err("[Config] Unable to execute 'getconfig' binary.");
            
            return false;
        }
        if (SpamFilter_Core::isWindows()) {
            // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.SystemExecFunctions.WarnSystemExec
            $response = shell_exec('"' . $binary . '" --get "' . CFG_FILE . '"');            
            $configuration = str_replace(" ","\n",$response);
        } else {
            $command = "%s %s";
            $command = sprintf($command, $binary, "--config");
            // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.SystemExecFunctions.WarnSystemExec
            $configuration = shell_exec($command);
        }

        // Check if the binary returned data
        if (empty($configuration)) {
            Zend_Registry::get('logger')->err("[Config] Unable to execute configuration binary.");

            return false;
        }

        // At this point $configuration contains a (very) large string with all config data.
        // This should be re-formatted to a Zend_Config object.
        // As long as [ZF-9088] is not implemented, we have to workaround the limitation of not being able to provide a string input to Zend_Config_Ini
        $iniContent = SpamFilter_Config_String::_parseIniFileContents($configuration);

        if (empty($iniContent) || (!is_array($iniContent))) {
            Zend_Registry::get('logger')->err("[Config] Unable to convert string to array.");

            return false;
        }

        // $iniContent now contains an array of the INI file, we can pass through to Zend_Config
        $config = new Zend_Config($iniContent, true);
        if (!isset($config)) {
            Zend_Registry::get('logger')->err("[Config] Unable to convert array to config object.");

            return false;
        }

        // And we're finally done with all the converting and rebuilding things, so lets just return what we need:
        return $config;
    }
    private function _saveBinaryContent($cfgData) {
        $binary = SpamFilter_Core::getConfigBinary();

        // Check if it is executable
        // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.FilesystemFunctions.WarnFilesystem
        if (!is_executable($binary)) {
            Zend_Registry::get('logger')->err("[Config] Unable to execute 'getconfig' binary.");

            return false;
        }
            $str = '';
            foreach ($cfgData as $k => $v){
                $str .= addslashes($k.'="'.$v.'"') . " ";
            }
            // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.SystemExecFunctions.WarnSystemExec
            shell_exec('"' . $binary . '" --save ' . CFG_FILE . ' "' . $str . '"');
            return true;
    }

    /**
     * getPassword
     * Returns the password used for internal API communication
     *
     * @return string hash
     *
     * @access private
     */
    public function getPassword()
    {
        Zend_Registry::get('logger')->info("[Config] Retrieving password for communication with local API.");
        // Used for binary content, that has to be converted to an INI
        $binary = SpamFilter_Core::getConfigBinary();

        // Check if it is executable
        // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.FilesystemFunctions.WarnFilesystem
        if (!is_executable($binary)) {
            Zend_Registry::get('logger')->err("[Config] Unable to execute 'getconfig' binary.");

            return false;
        }

        $command = "%s %s";
        $command = sprintf($command, $binary, "--accesstoken");
        // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.SystemExecFunctions.WarnSystemExec
        $hash    = shell_exec($command);

        // Check if the binary returned data
        if (empty($hash)) {
            Zend_Registry::get('logger')->err("[Config] Unable to execute configuration binary.");

            return false;
        }

        return trim($hash);
    }

    /**
     * GetConfig
     * Returns the configuration loaded
     *
     * @param string $section
     *
     * @return Zend_Config|stdClass object
     *
     * @access public
     */
    public function GetConfig($section = '')
    {
        // Returning only part of the object, the section requested.
        if (!empty($section)) {
            return $this->_configData->$section;
        }

        // Returning full object
        return $this->_configData;
    }

    /**
     * getOption
     * Returns one specific setting
     *
     * @param                 $key     Key used in configuration file
     * @param \Section|string $section Section to read from ([ ... ] )
     *
     * @return string|bool Requested value|False
     *
     * @access public
     * @static
     * @see    GetConfig()
     */
    public function getOption($key, $section = '')
    {
        Zend_Registry::get('logger')->debug("[Config] Requested value of '{$key}'");
        $config = $this->_configData;

        if (!empty($section)) {
            if (isset($config->$section->$key)) {
                Zend_Registry::get('logger')->debug("[Config] Returning value of '{$key}' in section '{$section}'");

                return $config->$section->$key;
            }
        } else {
            if (isset($config->$key)) {
                Zend_Registry::get('logger')->debug("[Config] Returning value of '{$key}'");

                return $config->$key;
            }
        }
        Zend_Registry::get('logger')->debug("[Config] Key doesn't exist, so returning false");

        return false;
    }

    /**
     * updateOption
     * Updates one specific option in the configuration
     *
     * @param string $key   Key to update
     * @param string $value Value to set for specified key
     *
     * @return Zend_Config_Ini object|False in case it failed
     *
     * @access public
     * @static
     * @see    GetConfig()
     * @see    WriteConfig()
     */
    public function updateOption($key, $value)
    {
        Zend_Registry::get('logger')->debug("[Config] Updating '$key'");
        $config = $this->_configData;
        if ((isset($config)) && (is_object($config))) {
            try {
                $x = $config->toArray();
            } catch (Exception $e) {
                Zend_Registry::get('logger')->err("[Config] Updating '{$key}' has failed.");

                return false;
            }
            // Direct key
            $x[$key] = $value;
            unset($x['Submit']);

            Zend_Registry::get('logger')->info("[Config] Key '{$key}' has been updated.");

            return $this->WriteConfig($x);
        }
        Zend_Registry::get('logger')->err("[Config] Updating '$key' has failed.");

        return false;
    }
    
    /**
     * 
     * @param type array $args - array of values which should be written to the config file at once
     * @return boolean
     */
    public function updateOptionsArray($args){
        $config = $this->_configData;
        if(!is_array($args)){
            Zend_Registry::get('logger')->debug("[Config] Provided data is not array. Returning False");      
            return false;
        }
        Zend_Registry::get('logger')->debug("[Config] Updating values ". serialize($args));      
        
        if ((isset($config)) && (is_object($config))) {
            try{
                $x = $config->toArray();
            } catch (Exception $ex) {
                Zend_Registry::get('logger')->err("[Config] Updating has failed.");

                return false;
            }
            foreach ($args as $key => $value){
                $x[$key] = $value;
            }
            unset($x['Submit']);
            Zend_Registry::get('logger')->info("[Config] Given parameters were written successfully.");
            return $this->WriteConfig($x);
        }       
        Zend_Registry::get('logger')->err("[Config] Updating configuration has failed.");

        return false;        
    }

    /**
     * WriteConfig
     * Write the full configuration file
     *
     * @param $cfgData Array of configuration data*
     *
     * @return bool Status code
     *
     * @access public
     */
    public function WriteConfig($cfgData)
    {
        if (is_array($cfgData)) {
            if(SpamFilter_Core::isWindows()){
                return $this->_saveBinaryContent($cfgData);
            }
            // Generate a clean config
            $config = new Zend_Config(array(), true);

            /**
             * In order to allow all types of characters including quotes
             * we will not use Zend Config Writter
             * @see https://trac.spamexperts.com/ticket/27306
             */

            // Generate config string
            $configStr = "";
            foreach ($cfgData as $k => $v){
                $configStr .= $k.'="'.str_replace('"', '\"',$v).'"' . "\n";
            }

            // Write values to the INI file
            try {
                // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.FilesystemFunctions.WarnFilesystem
                file_put_contents($this->_configFile, $configStr);
            } catch (Exception $e) {
                Zend_Registry::get('logger')->err($e->getMessage() . ' in ' . __FILE__ . ':' . __LINE__);
            }

            // Write config to variable
            $this->_configData = $this->_getIniContent($this->_configFile);

            // Write config to registry
            Zend_Registry::set('general_config', $this->_configData);

            // All done.
            return true;
        }

        return false;
    }

    /**
     * Set initial configuration options used during setup
     *
     * @param $data Array of data to set in the configuration
     *
     * @return bool Status
     *
     * @access public
     * @static
     * @see    LoadConfig()
     */
    public function setInitial($data = array())
    {
        // Generate a clean config
        /** @var stdClass $config */
        $config   = new Zend_Config(array(), true);
        /** @var stdClass $branding */
        $branding = new Zend_Config(array(), true);

        // Set all the options, with either overriden or default values.
        $config->language                = (!empty($data['language'])) ? $data['language'] : 'en';
        $config->spampanel_url           = (!empty($data['spampanel_url'])) ? $data['spampanel_url'] : '';
        $config->apihost                 = (!empty($data['apihost'])) ? $data['apihost'] : '';
        $config->apiuser                 = (!empty($data['apiuser'])) ? $data['apiuser'] : '';
        $config->apipass                 = (!empty($data['apipass'])) ? $data['apipass'] : '';
        $config->mx1                     = (!empty($data['mx1'])) ? $data['mx1'] : '';
        $config->mx2                     = (!empty($data['mx2'])) ? $data['mx2'] : '';
        $config->mx3                     = (!empty($data['mx3'])) ? $data['mx3'] : '';
        $config->mx4                     = (!empty($data['mx4'])) ? $data['mx4'] : '';
        $config->ssl_enabled             = (!empty($data['ssl_enabled'])) ? $data['ssl_enabled'] : '0';
        $config->auto_add_domain         = (!empty($data['auto_add_domain'])) ? $data['auto_add_domain'] : '1';
        $config->auto_del_domain         = (!empty($data['auto_del_domain'])) ? $data['auto_del_domain'] : '1';
        $config->provision_dns           = (!empty($data['provision_dns'])) ? $data['provision_dns'] : '1';
        $config->set_contact             = (!empty($data['set_contact'])) ? $data['set_contact'] : '1';
        $config->use_existing_mx         = (!empty($data['use_existing_mx'])) ? $data['use_existing_mx'] : '1';
        $config->add_domain_loginfail    = (!empty($data['add_domain_loginfail'])) ? $data['add_domain_loginfail']
            : '1';
        $config->bulk_force_change       = (!empty($data['bulk_force_change'])) ? $data['bulk_force_change'] : '0';
        $config->redirectback            = (!empty($data['redirectback'])) ? $data['redirectback'] : '0';
        $config->default_ttl             = (!empty($data['default_ttl'])) ? $data['default_ttl'] : '3600';
        $branding->brandname             = SpamFilter_Brand::getDefaultBrandname(
        ); // Not possible to override this, because we cannot check that without API access.
        $branding->brandicon             = SpamFilter_Brand::getDefaultIcon(
        ); // Not possible to override this, because we cannot check that without API access.
        $config->disable_reseller_access = (!empty($data['disable_reseller_access'])) ? $data['disable_reseller_access'] : '0';

        if (SpamFilter_Core::isCpanel()) // These features only works in cPanel for the moment.
        {
            $config->auto_update              = (!empty($data['auto_update'])) ? $data['auto_update'] : '1';
            $config->handle_only_localdomains = (!empty($data['handle_only_localdomains']))
                ? $data['handle_only_localdomains'] : '1';
            $config->bulk_change_routing      = (!empty($data['bulk_change_routing'])) ? $data['bulk_change_routing']
                : '1';
            $config->handle_extra_domains     = (!empty($data['handle_extra_domains'])) ? $data['handle_extra_domains']
                : '1';
            $config->add_extra_alias          = (!empty($data['add_extra_alias'])) ? $data['add_extra_alias'] : '0';
            $config->disallow_remote_login    = (!empty($data['disallow_remote_login']))
                ? $data['disallow_remote_login'] : '1';
            $config->handle_route_switching   = (!empty($data['handle_route_switching']))
                ? $data['handle_route_switching'] : '0';
        }

        // Finished, save the config.
        if(!SpamFilter_Core::isWindows()){
            $writer_config = new Zend_Config_Writer_Ini(
                array(
                    'config'   => $config,
                    'filename' => $this->_configFile
                )
            );
            // Lets write.
            $writer_config->write();

            // Reload changed config
            Zend_Registry::set('general_config', $config);

            $branding_config = new Zend_Config_Writer_Ini(
                array(
                    'config'   => $branding,
                    'filename' => '/etc/prospamfilter/branding.conf'
                )
            );
            // Lets write.
            $branding_config->write();

		    // Completed.
            return true;
        } else {
            return $config->toArray();
        }
    }
}
