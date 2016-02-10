<?php
/*
*************************************************************************
*                                                                       *
* ProSpamFilter                                                         *
* Bridge between Webhosting panels & SpamExperts filtering				*
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
class SpamFilter_Diagnostics
{
    private $_logger;
    protected $_settings;
    var $_paneltype;

    public function __construct()
    {
        $this->_logger    = Zend_Registry::get('logger');
        $this->_paneltype = strtolower(SpamFilter_Core::getPanelType());

        if (!Zend_Registry::isRegistered('general_config')) {
            Zend_Registry::get('logger')->debug("[Hooks] Initializing settings.. ");
            $settings = new SpamFilter_Configuration(CFG_PATH . DS . 'settings.conf'); // <-- General settings
        }
        $this->_settings = Zend_Registry::get('general_config');
    }

    public function run()
    {
        $reflect = new ReflectionObject($this);
        $status  = array();

        $jobs = $reflect->getMethods(ReflectionMethod::IS_PRIVATE);
        $this->_logger->debug("Starting diagnostics to determine addon integrity");
        foreach ($jobs as $job) {
            $jobname         = $job->getName();
            $jobname_partial = substr($jobname, 6);
            if ((substr($jobname, 0, 6) == "check_") && (method_exists($this, $jobname))
                && (!$this->checkSkip($jobname_partial))
            ) {
                $status[$jobname_partial] = array();
                $this->_logger->debug("Diagnostic starting: '{$jobname_partial}'");
                $status[$jobname_partial] = $this->$jobname();
                $this->_logger->debug("Finished diagnostic: '{$jobname_partial}'");
            }
        }
        $this->_logger->debug("Addon integritychecks have been completed.");

        return $status;
    }

    /*
         Functions that handle the actual checks.
     */
    private function check_PHP_version()
    {
        $return = $this->set_initial_array();
        if (version_compare(PHP_VERSION, '5.0.0', '<')) {
            $this->_logger->err("PHP version '" . PHP_VERSION . "' is being used instead of required 5.0.0");
            $return['critical'][] = "PHP Version < 5.0.0";
        }

        return $this->return_result($return);
    }

    private function check_PHP_extensions()
    {
        $return = $this->set_initial_array();

        $obligatoryExtensions = array(
            'OpenSSL'             => 'openssl_open', //Optionally used in API communication
            'Fopen'               => 'fopen', //Used in branding regeneration
            'Curl'                => 'curl_init', //Used in API communication (software + spampanel API)
            'Shell_Exec'          => 'shell_exec', //used in install/upgrade/branding regeneration
            'system'              => 'system', //used in install/upgrade/branding regeneration
            'chown'               => 'chown' //used in install/upgrade
        );

        // Check all available PHP functions and return false if we don't have it (which is a problem!)
        foreach ($obligatoryExtensions as $ext => $functionToCheck) {
            if (!function_exists($functionToCheck)) {
                $this->_logger->err("Addon is missing support for {$functionToCheck}");
                if ($functionToCheck == "openssl_open") {
                    $return['critical'][] = "Missing {$ext} support for PHP.";
                } else {
                    if ($functionToCheck <> "openssl_open") {
                        $return['critical'] = "Missing mandatory PHP Module or function: {$ext}. ";
                    } else {
                        $return['warning'] = "Missing optional PHP Module or function: {$ext}. ";
                    }
                }
            }
        }

        return $this->return_result($return);
    }

    private function check_configuration_binary()
    {
        $return = $this->set_initial_array();

        $bits = array('32', '64');
        foreach ($bits as $bit) {
            $this->_logger->debug("Checking {$bit}bits binary!");

            $configBinary = BASE_PATH . DS . 'bin' . DS . 'getconfig';
            if ($bit == 64) {
                $configBinary .= "64";
            }
            $this->_logger->debug("Checking {$bit}bits binary - Located at '{$configBinary}'");
            if (!file_exists($configBinary)) {
                $this->_logger->err("Configuration binary for {$bit}bit is missing!");
                $return['critical'][]
                    = "Configuration binary for {$bit}bit is missing and should exist at '{$configBinary}'";
            } else {
                // Check if the permissions for the apipass binary are in place.
                $perms = fileperms($configBinary);
	 	$perms = substr(sprintf('%o', $perms), -4);
                if ($perms != 6755) {
                    $return['critical'][]
                        = "APIPass binary for {$bit}bits has incorrect permissions ({$perms}) instead of 6755.";
                }
            }
        }

        return $this->return_result($return);
    }

    private function check_configuration_permissions()
    {
        $return = $this->set_initial_array();

        $settings_file = CFG_PATH . DS  . "settings.conf";
        if (file_exists($settings_file)) {
            $perms = (int)file_perms($settings_file);
            if (!empty($perms)) {
                // Check for the 3rd bit (meant for 'Other')
                $sub_perm = substr($perms, -1);
                if ($sub_perm > 0) {
                    if (isset($rv['reason']) && is_array($rv['reason'])) {
                        try {
                            $return['warning'][] = "The settingsfile is world-readable.";
                        } catch (Exception $e) {
                            $this->_logger->debug("Caught settingsfile error-error.");
                            $return['critical'][] = "Unable to read the configuration file.";
                        }
                    }
                }
            }
        }

        return $this->return_result($return);
    }

    private function check_panel_version()
    {
        $return = $this->set_initial_array();

        $panel = new SpamFilter_PanelSupport();
        if (!$panel->minVerCheck()) {
            $return['critical'][] = "The version of your controlpanel is not supported by the addon";
        }

        return $this->return_result($return);
    }

    private function check_addon_version()
    {
        $return = $this->set_initial_array();

        $tier          = (!empty($this->_settings->updatetier)) ? $this->_settings->updatetier : 'stable';
        $is_updateable = SpamFilter_Version::updateAvailable($tier, true);

        if (!isset($is_updateable)) {
            $return['warning'][] = "Unable to check for updates.";
        } elseif ($is_updateable === true) {
            $return['warning'][] = "You are not using the latest version of the addon.";
        }

        return $this->return_result($return);
    }

    private function check_hashes()
    {
        $return = $this->set_initial_array();

        if (!file_exists(BASE_PATH . DS .'application' . DS . 'hashes.json')) {
            $return['critical'][] = 'The file containing filehashes is missing, unable to check this in-depth.';

            return $this->return_result($return);
        }

        $hashfile = file_get_contents(BASE_PATH . DS .'application' . DS . 'hashes.json');
        if ((!isset($hashfile)) || (empty($hashfile))) {
            $return['critical'][] = 'The file containing filehashes is empty, unable to check this in-depth.';

            return $this->return_result($return);
        }

        chdir(BASE_PATH);
        $hashfiles = Zend_Json::decode($hashfile);

        $dir_iterator = new RecursiveDirectoryIterator(".");
        $iterator     = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $filename = $file->getPathname();

                /**
                 * Don't validate hash for brandicon
                 * @see https://trac.spamexperts.com/ticket/17037
                 * @see https://trac.spamexperts.com/ticket/18107
                 */
                if (in_array(strtolower(basename($filename)), array('brandicon.png', 'prospamfilter.gif', 'psf.tar.bz2', 'install.json', 'se-logo.png'))) {
                    continue;
                }

                $my_hash = sha1_file($file);
                if (isset($hashfiles[$filename])) // file is monitored
                {
                    if ($hashfiles[$filename]['sha1'] !== $my_hash) {
                        /* Do not warn about files with changed hashbangs @see https://trac.spamexperts.com/ticket/17451 */
                        if (!preg_match('~\./(bin|frontend)/.*\.php$~', $filename)) {
                            $return['critical'][] = "Hash mismatch: \"{$filename}\"";
                        }
                    }
                }
            }
        }

        // return data
        return $this->return_result($return);
    }

    private function check_hooks()
    {
        $return = $this->set_initial_array();

        switch(strtolower($this->_paneltype)){                                       
            case "cpanel":
            $panel = new SpamFilter_PanelSupport_Cpanel;
            $registeredHooks = $panel->listHooks();
            $file = BASE_PATH . '/bin/hook.php';
            $files = array('hook.php' => BASE_PATH . '/bin/hook.php');           
            $hooks =  SpamFilter_PanelSupport_Cpanel::getHooksList();

            foreach ($hooks as $hook) {
                if (isset($hook['category']) && isset($hook['event'])
                    && !$panel->isHookExists($registeredHooks, $hook['category'], $hook['event'], $hook['stage'], $file)) {
                    $return['critical'][] = "The hook '" . $hook['category'] . "::" . $hook['event'] . "' does not exist.";
                }
            }
            break;
            case "plesk":
                $files = array(
                    'EventListener'            => PLESK_DIR . 'admin' . DS . 'plib' . DS . 'registry' . DS . 'EventListener' . DS . 'prospamfilter.php',
                );
                break;

        }
            
            foreach ($files as $hook => $file) {
                $this->_logger->debug("Checking hook: '{$hook}' (file: '{$file}')");
                // check if the file exists
                if (!file_exists($file)) {
                    $this->_logger->debug("Missing hook: {$file}");
                    $return['critical'][] = "The hook '{$hook}' does not exist.";
                } elseif(!SpamFilter_Core::isWindows() && !is_executable($file)) {  // In windows checking for executable must be disabled. There will be always returned false for *.php files.
                    $this->_logger->debug("The hook file '{$file}' is not executable.");
                    $return['critical'][] = "The hook '{$hook}' is not executable.";
                }
            }
               
        // do check here
        return $this->return_result($return);
    }

    /*
         Internal functions
     */
    private function check_symlinks()
    {
        $return = $this->set_initial_array();

        switch ($this->_paneltype) {
            case "cpanel";
                $files = array(
                    "cPanel frontend (x3)"         => "/usr/local/cpanel/base/frontend/x3/prospamfilter",
                    "cPanel frontend (x3mail)"     => "/usr/local/cpanel/base/frontend/x3mail/prospamfilter",
                    "WHM frontend"                 => "/usr/local/cpanel/whostmgr/docroot/cgi/addon_prospamfilter.cgi",
                    "WHM frontend - icon"          => "/usr/local/cpanel/whostmgr/docroot/themes/x/icons/prospamfilter.gif",
                );
                break;

            case "plesk":
                $files = array(
                    "EventListener"             => PLESK_DIR . 'admin' . DS . 'plib' . DS . 'registry' . DS . 'EventListener' . DS . 'prospamfilter.php',
                    "Plesk Frontend"            => PLESK_DIR . DS . 'admin' . DS . 'htdocs' . DS . 'modules' . DS . 'prospamfilter',
                );
                break;

        }

        // do check here
        foreach ($files as $symlink => $file) {
            // check if the symlink exists
            if (!is_link($file) && !file_exists($file)) {
                $this->_logger->debug("Missing symlink: {$file}");
                $return['critical'][] = "The symlink for '{$symlink}' ({$file}) does not exist.";
            }
        }

        // return result.
        return $this->return_result($return);
    }

    private function check_Controlpanel_API()
    {
        // Check if we can communicate with the controlpanel's API
        $return = $this->set_initial_array();

        $panel = new SpamFilter_PanelSupport();
        if (!$panel->apiAvailable()) {
            $return['critical'][] = 'Unable to communicate with the controlpanel API';
        }

        return $this->return_result($return);
    }

    private function check_Spamfilter_API()
    {
        // Check if we can work with the SE API
        $return = $this->set_initial_array();

        $api = new SpamFilter_ResellerAPI;
        if (!$api) {
            $return['critical'][] = 'Unable to initialize Spamfilter API.';
        }

        $apimethods = $api->productslist()->get(array());


        if (empty($apimethods) ||
            ((isset($apimethods['reason']) && $apimethods['reason'] == "API_REQUEST_FAILED"))
        ) {
            $return['critical'][] = 'Unable to communicate with the Spamfilter API.';
            return $this->return_result($return);
        } elseif (isset($apimethods['reason']) && $apimethods['reason'] == "API_IP_ACCESS_ERROR") {
            //@see https://trac.spamexperts.com/ticket/22536
            $return['critical'] = $apimethods['additional'];
            return $this->return_result($return);
        } elseif (isset($apimethods['reason']) && $apimethods['reason'] == "INVALID_API_CREDENTIALS") {
            //@see https://trac.spamexperts.com/ticket/25183
            $return['critical'] = $apimethods['additional'];
            return $this->return_result($return);
        } elseif (isset($apimethods['reason']) && $apimethods['reason'] == "API_USER_INACTIVE") {
            $return['critical'] = $apimethods['additional'];
            return $this->return_result($return);
        } elseif (isset($apimethods['reason']) && $apimethods['reason'] == "API_ACCESS_DISABLED") {
            $return['critical'] = $apimethods['additional'];
            return $this->return_result($return);
        }

        if (!is_array($apimethods)) {
            // We need to explicitly check whether we got a string or json value
            try {
                $data = Zend_Json::decode($apimethods);
            } catch (Exception $e) {
                $return['critical'][] = 'Unable to communicate with the Spamfilter API.';

                return $this->return_result($return);
            }
        }

        return $this->return_result($return);
    }

    /**
     * @access private
     */
    private function check_symlink_to_PHP5_binary()
    {
        $return = $this->set_initial_array();

        // check if the symlink exists
        if(SpamFilter_Core::isWindows() && !file_exists(DEST_PATH . DS . 'bin' . DS . 'prospamfilter_php')){
            $symlink = BASE_PATH . DS . 'bin' . DS . 'prospamfilter_php';
            $this->_logger->debug("Missing link to the PHP5 binary ($symlink)");
            $return['critical'][] = "The link for PHP5 binary '{$symlink}' does not exist.";
        } elseif (!SpamFilter_Core::php5BinaryLinkExists()) {
            $symlink = SpamFilter_Core::PHP5_BINARY_SYMLINK;
            $this->_logger->debug("Missing link to the PHP5 binary ($symlink)");
            $return['critical'][] = "The link for PHP5 binary '{$symlink}' does not exist.";
        }

        return $this->return_result($return);
    }

    private function set_initial_array()
    {
        $return             = array();
        $return['warning']  = array();
        $return['critical'] = array();

        return $return;
    }

    private function checkSkip($job)
    {
        $this->_logger->debug("Checking if we need to skip '{$job}'..");
        if ($this->_paneltype <> "cpanel") {
            // Other panels do not use the binary
            if ($job == "configuration_binary") {
                $this->_logger->debug("Skipping check {$job}..");

                return true;
            }
        }

        /** @see https://trac.spamexperts.com/ticket/17036 */
        if ($this->_paneltype == "cpanel") {
            // Other panels do not use the binary
            if ($job == "symlink_to_PHP5_binary") {
                $this->_logger->debug("Skipping check {$job}..");

                return true;
            }
        }
        
        if (SpamFilter_Core::isWindows()){
            if($job == 'symlink_to_PHP5_binary'){
                $this->_logger->debug("Skipping check {$job}..");
                return true;
            }
        }

        return false;
    }

    private function return_result($return)
    {
        if (count($return['critical']) == 0) {
            unset($return['critical']);
        }

        if (count($return['warning']) == 0) {
            unset($return['warning']);
        }

        if ((!isset($return['critical'])) && (!isset($return['warning']))) {
            $return = true;
        }

        return $return;
    }
}
