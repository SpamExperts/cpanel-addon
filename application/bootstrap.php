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
* @since     2.0
*/
// Override maximum execution time.
ini_set ('session.auto_start', '0' );
$lim = ini_get('max_execution_time');
if ( $lim > 0 && $lim < 300 )
{
	@set_time_limit(300);
	@ini_set('max_execution_time', 300);
}

// Setup default timezone (as we might be skipping php.ini)
$tz = ini_get('date.timezone');
$tz = ($tz) ? $tz : 'UTC';
date_default_timezone_set($tz);

$isWindows = stripos(PHP_OS, 'win') === 0;

// Setup some global defines.
if( (isset($path_override)) && ($path_override) )
{
	// Do nothing
} else {
        if(!defined('DS')){
            define('DS', DIRECTORY_SEPARATOR);
        }
        if(isset($_ENV['plesk_dir']) && $isWindows){
            if(!defined('PLESK_DIR')){
                define('PLESK_DIR', $_ENV['plesk_dir']);
            }
            if(!defined('BASE_PATH')){
                // App Installation Directory
                define('BASE_PATH', $_ENV['ProgramFiles'] . DS . "SpamExperts" . DS . "Professional Spam Filter");
            }
            if(!defined('CFG_PATH')){
                //App Settings Directory
                define('CFG_PATH', $_ENV['ProgramData'] . DS . "SpamExperts" . DS . "config");
            }
            if(!defined('TMP_PATH')){
                // Temporary directory
                define('TMP_PATH', $_ENV['ProgramData'] . DS . "SpamExperts" . DS . "tmp");
            }
     } else {

            if(!defined('BASE_PATH')) define("BASE_PATH", '/usr/local/prospamfilter' );

            if(!defined('TMP_PATH')){
                define('TMP_PATH', BASE_PATH . "/tmp/");
            }

            // One global config path.
            if(!defined('CFG_PATH')) define("CFG_PATH", '/etc/prospamfilter');
        }

    defined('PLESK_DIR') or define('PLESK_DIR', '/usr/local/psa/');
}

if(!defined('CFG_FILE')) define('CFG_FILE', CFG_PATH . DS . 'settings.conf');
if(!defined('LIB_PATH')) define('LIB_PATH', BASE_PATH . DS . 'library' . DS);

if(file_exists(CFG_PATH . DS ."debug")) //<-- Only enable error logging when this file exists.
{
	$debug = true;
    defined('E_DEPRECATED')
        ? error_reporting( E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED )
        : error_reporting( E_ALL & ~E_NOTICE & ~E_STRICT );
    @ini_set('display_errors', 'On');
} else {
    $debug = false;
    @ini_set('display_errors', 'Off');
}

// Extend include path
if (defined('IS_PLESK') && IS_PLESK) {
    // Custom override to fix problems with their code.
    $new_path = str_replace(array(                                  //?? TODO ??
         ':/opt/psa/admin/htdocs/domains/databases/phpMyAdmin',
         ':/opt/psa/admin/htdocs/domains/databases/phpPgAdmin',
         ':/usr/local/psa/admin/htdocs/domains/databases/phpMyAdmin',
         ':/usr/local/psa/admin/htdocs/domains/databases/phpPgAdmin',
    ), '', get_include_path());
    set_include_path($new_path . PATH_SEPARATOR . LIB_PATH);
} else {
    set_include_path(LIB_PATH);
}

// Include Zend_Autolooader
try {

    /** @see https://trac.spamexperts.com/ticket/16938 */
    if (!class_exists('Zend_Loader_Autoloader')) {
	    require_once 'Zend' . DS . 'Loader' . DS . 'Autoloader.php';
    }

	// Configure the autoloader to include our namespaces
	$autoloader = Zend_Loader_Autoloader::getInstance();
	$autoloader->setFallbackAutoloader(true);
	$autoloader->registerNamespace('SpamFilter_');
	$autoloader->registerNamespace('IDNA_');
	$autoloader->registerNamespace('Twitter_');
} catch (Exception $e) {
	echo "Failed to initialize the autoloader (" . $e->getMessage() . ")";
	exit( 1 );
}

if( (isset($debug_enabled)) && ($debug_enabled) )
{
	// Enable debug.
	$debug = true;
}

try {
	#$logger = SpamFilter_Core::initLogging( );
	if( $debug ) //<-- Only enable debug logging when this has been enabled.
	{
		if(!defined('PSF_DEBUG')) define("PSF_DEBUG", true );
		$logger = SpamFilter_Core::initLogging( true, true);
		$logger->debug("[Bootstrap] Debug logging enabled");
	} elseif(file_exists(CFG_PATH . DS ."logging")) {
		if(!defined('PSF_DEBUG')) define("PSF_DEBUG", false );
		$logger = SpamFilter_Core::initLogging( true, false );
		$logger->debug("[Bootstrap] Normal logging enabled");
	} else {
		if(!defined('PSF_DEBUG')) define("PSF_DEBUG", false );
		$logger = SpamFilter_Core::initLogging( false, false );
		// No point in writing "logging disabled" if we do not log.
	}
} catch (Exception $e) {
	echo "Failed to initialize logging core";
	exit( 1 );
}

/** @noinspection PhpUndefinedClassInspection */
if (SpamFilter_Core::isSessionInitRequired() && SpamFilter_Core::isCpanel()) {
    Zend_Session::setOptions(array(
        "strict"    => true,
        "save_path" => TMP_PATH . DS . "sessions" . DS
    ));

    if (Zend_Session::isStarted()) {
        @Zend_Session::regenerateId();
    } else {
        if (!empty($_SERVER['REQUEST_METHOD']) && !isset($_SESSION)) {
            try {
                Zend_Registry::get('logger')->debug("Attempting to start session");
                @Zend_Session::start(false);
            } catch (Zend_Session_Exception $e) {
                Zend_Registry::get('logger')->err("Starting session failed, attempting to correct issue..");
            }
        }
    }

    // Attempt to workaround CPANEL-#1775881 (SE-#11909)
    // @TODO: Chase cPanel to fix this on their end and remove this code.
    if (isset($_SERVER['SERVER_PORT'])) {
        if (in_array($_SERVER['SERVER_PORT'], array('2086', '2087', '2083'))) {
            $sid = session_id();
            if (!empty($sid)) {
                $sessionfile = session_save_path() . '/' . "sess_" . $sid;
                // Change permissions of session file, to be able to use it
                Zend_Registry::get('logger')->debug("Changing permissions for '{$sessionfile}'... ");
                @chmod($sessionfile, 0666);
            }
        }
    }
}

try {
	$conf = SpamFilter_Core::initConfig( CFG_FILE );
} catch (Exception $e) {
	echo "Failed to initialize configuration core";
	Zend_Registry::get('logger')->err("Failed to initialize configuration core");
	exit( 1 );
}

$config       = $conf->GetConfig();
$language     = !empty($config->language) ? $config->language : 'en';

$languageFile = BASE_PATH . DS . "translations" . DS . "addons" . DS . "compiled" . DS . $language . DS . "LC_MESSAGES". DS . "se.mo";
Zend_Registry::set(
    'translator',
    new Zend_Translate(array(
            'adapter'        => 'gettext',
            'content'        => is_file($languageFile) ? $languageFile : '',
            'locale'         => $language,
            'disableNotices' => true,
        )
    )
);

// Load functions
require_once 'functions.php';
