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

class SpamFilter_Core
{
    const PHP5_BINARY_SYMLINK = '/usr/local/bin/prospamfilter_php';

    /**
     * getBitType
     * Returns whether it is running 32 or 64 bits
     *
     *
     * @return string Amount of bits available (32/64)
     *
     * @access public
     * @static
     */
    public static function getBitType()
    {
        return (int)(8 * PHP_INT_SIZE);
    }

	/**
	 * getConfigBinary
	 * Return the full path to the getconfig binary
	 *
     * @param int $bits Optional override of specific byte-type binary
	 *
	 * @return string Path to the getconfig binary
	 *
	 * @access public
	 * @static
	 * @see getBitType()
	 */
	public static function getConfigBinary( $bits = null )
	{
            if(self::isWindows()){
                $binary = 'getconfig.exe';
                return trim( BASE_PATH . DS ."bin". DS . $binary);
            } else {
                if(empty($bits))	// No input given, so lets fall back.
                {
                    $bits = self::getBitType();
                }
            
            if ($bits == 64 && (file_exists(BASE_PATH . '/bin/getconfig64'))) {
                    #Zend_Registry::get('logger')->debug("[Core] Using 64-bits getconfig binary");
                    $binary = 'getconfig64';
                } else {
                    #Zend_Registry::get('logger')->debug("[Core] Using 32-bits getconfig binary");
                    $binary = 'getconfig';
                }
            }
            return trim( BASE_PATH . "/bin/{$binary}" );
        }

	/**
	 * isCpanel
	 * Returns whether the used panel is cPanel
	 *
	 *
	 * @return bool Whether it is cPanel or not
	 *
	 * @access public
	 * @static
	 * @see getPanelType()
	 */
	public static function isCpanel()
	{
		$type = strtoupper( self::getPanelType() );

		return ($type == "CPANEL") ? true : false;
	}

	/**
	 * getPanelType
	 * Returns the panel type
	 *
	 *
	 * @return string Type of panel in use (uppercased)
	 *
	 * @access public
	 * @static
	 */
	public static function getPanelType()
	{
        if (file_exists('/usr/local/cpanel/')) {
			Zend_Registry::get('logger')->debug("[Core] Panel is determined to be cPanel (or WHM).");

			return 'CPANEL';
		}
                
		Zend_Registry::get('logger')->err("[Core] Paneltype cannot be determined.");

		return 'UNKNOWN';
	}

	/**
	 * initLogging
	 * Initializes logging system
	 *
     * @param bool $logging Enable logging or not
     * @param bool $debug   Enable debug-level logging
	 *
	 * @return Spamfilter_Logger object
	 *
	 * @access public
	 * @static
	 */
	public static function initLogging( $logging = false, $debug = false )
	{
		// check if we want $debug
        if ($logging) {
			// Syslog it.
			$writer = new Zend_Log_Writer_Syslog(
							array(
								'application' => 'ProSpamFilter'
								)
							);
		} else {
			// Use dummy writer
			$writer = new Zend_Log_Writer_Null();
		}

        if ($debug) {
			// Receive DEBUG messages and below
			$writer->addFilter(new Zend_Log_Filter_Priority( Zend_Log::DEBUG ));
		} else {
			// Receive UP TO the DEBUG level of messages.
			$writer->addFilter(new Zend_Log_Filter_Priority( Zend_Log::CRIT ));
		}

		#$logger = new Zend_Log($writer);
		$logger = new SpamFilter_Logger( $writer ); //<-- Custom logging function

		Zend_Registry::set('logger', $logger);

		return $logger;
	}

	public static function initConfig( $configFile )
	{
		$conf = new SpamFilter_Configuration( $configFile );

		return $conf;
	}

    final static public function getUsername()
	{
        /** @var $logger SpamFilter_Logger */
        /** @noinspection PhpUndefinedClassInspection */
        $logger = Zend_Registry::get('logger');

        if (isset($_ENV['REMOTE_USER']) && (!empty($_ENV['REMOTE_USER']))) {
            $logger->debug("[Core] Returning username ({$_ENV['REMOTE_USER']}) retrieved from env as remote_user");

			return $_ENV['REMOTE_USER'];
		} elseif (isset($_SERVER['REMOTE_USER']) && (!empty($_SERVER['REMOTE_USER']))) {
            $logger->debug("[Core] Returning username ({$_SERVER['REMOTE_USER']}) retrieved from $_SERVER (REMOTE_USER)");

			return $_SERVER['REMOTE_USER'];

        /** @see https://trac.spamexperts.com/ticket/19646 */
        } elseif (!empty($_SERVER['USER']) && 'psaadm' != $_SERVER['USER']) {
            $logger->debug("[Core] Returning username ({$_SERVER['USER']}) retrieved from \$_SERVER['user']");

			return $_SERVER['USER'];
        } elseif (isset($_SERVER['USERNAME']) && (!empty($_SERVER['USERNAME']))) {
            $logger->debug("[Core] Returning username ({$_SERVER['USERNAME']}) retrieved from $_SERVER (username)");

			return $_SERVER['USERNAME'];
        } elseif (isset($GLOBALS['session']->_login) && (!empty($GLOBALS['session']->_login))) {
            $logger->debug("[Core] Returning username ({$GLOBALS['session']->_login}) retrieved from GLOBALS (Plesk)");

			return $GLOBALS['session']->_login;
		} elseif ('cli' == PHP_SAPI && self::isCpanel()) {
            $logger->debug("[Core] Returning username (root) as all CLI scripts in cPanel are executed for the 'root' user");

            return 'root';
        } elseif (!empty($_SESSION['auth']['isAuthenticatedAsRoot'])) {
            $logger->debug("[Core] Returning username (root) as I see the ['auth']['isAuthenticatedAsRoot'] variable is set in Plesk");

            return 'root';
        }

        $logger->err("[Core] Unable to retrieve the username.");

		return false;
	}

    final static public function getDomainsCacheId()
    {
        $username = self::getUsername();

        if (empty($username)) {
            throw new InvalidArgumentException('Current username should not be empty');
        }

        return 'alldomains_' . sha1($username);
    }

    final static public function invalidateDomainsCaches()
    {
        try {
            $prefix = self::getDomainsCacheId();
        } catch (InvalidArgumentException $e) {
            $prefix = 'alldomains_';
        }

        /** @noinspection PhpUndefinedClassInspection */
        foreach (SpamFilter_Panel_Cache::listMatches("$prefix") as $cacheId) {
            /** @noinspection PhpUndefinedClassInspection */
            SpamFilter_Panel_Cache::clear($cacheId, false);
        }
    }

	/**
	 * Checks whether the requirements are met
	 *
     * @param bool          $fullCheck Execute a full check or just a brief one (defaults to full)     *
     * @param array         $options
	 *
	 * @return array Statuscode and error messages.
	 *
	 * @access public
	 * @static
	 * @see  PanelApiTest()
	 * @see  isCpanel()
	 */
	public static function selfCheck( $fullCheck = true, $options = array() )
	{
		$rv = array();
		$rv['status'] = true;
		$rv['reason'] = array();
		$rv['critital'] = false;

		$obligatoryExtensions = array(
			'OpenSSL' 		=>	 	'openssl_open',		//Optionally used in API communication
			'Fopen'  		=> 		'fopen',		//Used in branding regeneration
			'Curl'   		=> 		'curl_init',		//Used in API communication (software + spampanel API)
			'Shell_Exec'  		=> 		'shell_exec',		//used in install/upgrade/branding regeneration
			'system'		=> 		'system',		//used in install/upgrade/branding regeneration
			'chown'			=>		'chown'			//used in install/upgrade
		);

		// Check all available PHP functions and return false if we don't have it (which is a problem!)
        foreach ($obligatoryExtensions as $ext => $functionToCheck) {
            if (!function_exists($functionToCheck)) {
		    	Zend_Registry::get('logger')->emerg("Addon is missing support for {$functionToCheck}");
                if ($functionToCheck == "openssl_open") {
                    array_push(
                        $rv['reason'],
                        "Missing {$ext} support for PHP. This simply means you cannot use SSL between cPanel and the spamfilter or between the addon and cPanel."
                    );
		    	} else {
                    if ($functionToCheck <> "openssl_open") {
						// Everything, except OpenSSL is mandatory.
						array_push($rv['reason'], "Missing required PHP Module or function: {$ext}. ");
						$rv['critital'] = true; // We cannot work without these addons.
					} else {
						array_push($rv['reason'], "Missing optional PHP Module or function: {$ext}. ");
					}
				}
			#return false;
		    }
		}

		// Ok, apparently we have all required functions. Now lets check some other info.
        if (version_compare(PHP_VERSION, '5.0.0', '<')) {
			// This plugin is built for PHP5 (due to OOP), so it shouldn't work when PHP4 is being used.
            Zend_Registry::get('logger')->emerg(
                "PHP version '" . PHP_VERSION . "' is being used instead of required 5.0.0"
            );
		    	array_push($rv['reason'], "PHP Version < 5.0.0");
				$rv['critital'] = true; // This is critical enough to be a problem. PHP5 is required, at the very least verson 5.0.0.
			#return false;
		}

        if ($fullCheck) {
			if( self::isCpanel() ) //the configuration binary is only being used by cPanel.
			{
				$configBinary = self::getConfigBinary();
				// Check if the configuration binary is there.
                if (!file_exists($configBinary)) {
					// File does not exist!
						Zend_Registry::get('logger')->emerg("Configuration binary is missing!");
						array_push($rv['reason'], "Configuration binary missing and should exist at '{$configBinary}'");
					#return false;
				} else {
					// Check if the permissions for the apipass binary are in place.
					$perms = fileperms( $configBinary );
					$perms = substr(sprintf('%o', $perms), -4);
                    if ($perms != 6755) {
						//$rv['reason'][] = "APIPass binary has incorrect permissions ({$perms}) instead of 6755. Unable to fix this automatically. Please execute: 'chmod 6755 {$configBinary}' via SSH to fix this.";
						// Permissions are not correct
							$rv = trim( shell_exec( "chown root:root {$configBinary} && chmod +s {$configBinary} && echo \"OK\" || echo \"NOTOK\"") );
							if ($rv != "OK")
							{
								Zend_Registry::get('logger')->emerg("APIPass permissions are not correct ({$perms}). Please chmod it at with +s!");
								array_push($rv['reason'], "APIPass binary has incorrect permissions ({$perms}) instead of 6755. Unable to fix this automatically. Please execute: 'chmod +s {$configBinary}' via SSH to fix this.");
							} else {
								Zend_Registry::get('logger')->info("Fixed the permissions of APIPass, they were '{$perms}' but now changed to '+s'.");
							}
					}
				}
			}

			// Security check, only for cPanel (now) since that doesnt lock users in their homedirs.
            if (self::isCpanel()) {
				$apipasspath = CFG_PATH . "/settings.conf";
                if (file_exists($apipasspath)) {
					$perms = (int)file_perms( $apipasspath );
                    if (!empty($perms)) {
						// Check for the 3rd bit (meant for 'Other')
						$sub_perm = substr( $perms, -1 );
                        if ($sub_perm > 0) {
                            if (is_array($rv['reason'])) {
                                try {
                                    @array_push(
                                        $rv['reason'],
                                        (string)"The settingsfile is world-readable. Please make sure the file is chmod 660, unless you did this on purpose."
                                    );
                                } catch (Exception $e) {
									Zend_Registry::get('logger')->debug("Caught settingsfile error-error.");
								}
							}
						}
					}
				}
			}

			// Version check of the control panel used.
            /** @var $panel SpamFilter_PanelSupport_Cpanel */
			$panel = new SpamFilter_PanelSupport( null, $options );
            if ($panel) {
				$minVerCheck = $panel->minVerCheck();
                if ($minVerCheck) {
					try {
						$version = $panel->getVersion();
                        if (!empty($version)) {
                            @array_push(
                                $rv['reason'], "Your controlpanel is running an unsupported version. ({$version})"
                            );
					}
                    } catch (Exception $e) {
						Zend_Registry::get('logger')->err("Error while running version check (reporting error)");
					}
				}
			}

		} // end fullCheck

		// All checks succeeded, very good!
        if (count($rv['reason']) > 0) {
		    	$rv['status'] = false;
		}

		return $rv;
	}

	/**
	 * PanelApiTest
	 * Tests whether the panel provided API is available
	 * Actually redirects the request to the specific panel driver.
	 *
	 *
	 * @return bool Status of controlpanel API
	 *
	 * @access public
	 * @static
	 */
	public static function PanelApiTest()
	{
		// Check if we can communicate with the "foreign" API as well.

        /** @var $panel SpamFilter_PanelSupport_Cpanel */
		$panel = new SpamFilter_PanelSupport( );
        if (!$panel) {
			Zend_Registry::get('logger')->err("[PanelApiTest] Not possible to check without panel driver");

			return false; // cannot check
		}

		$status = $panel->apiAvailable();
		Zend_Registry::get('logger')->err("[PanelApiTest] Status: {$status}");

		return $status;
	}

	/**
	 * ApiTest
	 * Tests whether our API is available
	 *
	 *
	 * @return bool Status of API
	 *
	 * @access public
	 * @static
	 * @see ApiVersion()
	 */
	public static function ApiTest()
	{
		$check = self::ApiVersion();
        if (!empty($check)) {
			return true;
		}

		return false;
	}

	/**
	 * ApiVersion
	 * Returns the version of the API
	 *
	 *
	 * @return string API version
	 *
	 * @access public
	 * @static
	 * @see getApi()
	 */
	public static function ApiVersion()
	{
		$api = new SpamFilter_ResellerAPI();

		return $api->version()->get( array() ) ;
	}

	/**
	 * isTesting
	 * Returns whether testing mode is enabled (only applies on updates)
	 *
	 *
	 * @return bool Whether testing is enabled or disabled.
	 *
	 * @access public
	 * @static
	 */
	public static function isTesting()
	{
        if (file_exists(CFG_PATH . "/testing")) {
			// Manual override
			return true;
		}

		return false;
	}

	/**
	 * GetServerName
	 * Returns the FQDN servername, used in routes if required.
	 *
	 *
	 * @return string Hostname of the server
	 *
	 * @access public
	 * @static
	 */
	public static function GetServerName()
	{
		// Use servername (only works when being called from WEB, not CLI)
        if (!empty($_SERVER['SERVER_NAME'])) {
			return trim( $_SERVER['SERVER_NAME'] );
		}

		// Fallback to a generic method which should provide us what we need.
		$hostname = trim(shell_exec('hostname -f'));
        if (!empty($hostname)) {
			return $hostname;
		}

		// Third fallback option, just in case we need that.
		$hostname = php_uname('n');
        if (!empty($hostname)) {
			return trim($hostname);
		}

		return false;
	}

	/**
	 * validateDomain
	 * Validates whether the provided domain is correct or not
	 *
     * @param string $domain Domainname to validate
	 *
	 * @return bool
	 *
	 * @access public
	 * @static
	 */
	public static function validateDomain($domain)
	{
        if (!is_scalar($domain)) {
            return false;
        }

        if ('localhost' == strtolower($domain)) {
            return true;
        }

        if (function_exists('idn_to_ascii')) {
            $domain = idn_to_ascii($domain);
        } else {
            $idn = new IDNA_Convert;
            $domain = $idn->encode($domain);
        }

        return preg_match('~^[A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+$~i', $domain);
	}

    /**
     * timePassed
     * Returns the amount of time passed in human readable format.
     *
     * @param int $pastTimestamp Timestamp to check against
     *
     * @return string Human readable notation of passed time
     *
     * @access public
     * @static
     */
    public static function timePassed($pastTimestamp)
    {
        $currentTimestamp = time();
        $timePassed       = $currentTimestamp - $pastTimestamp; //time passed in seconds
        // Minute == 60 seconds
        // Hour == 3600 seconds
        // Day == 86400
        // Week == 604800
        $elapsedString = "";
        if ($timePassed > 604800) {
            $weeks = floor($timePassed / 604800);
            $timePassed -= $weeks * 604800;
            $elapsedString = $weeks . " weeks, ";
        }
        if ($timePassed > 86400) {
            $days = floor($timePassed / 86400);
            $timePassed -= $days * 86400;
            $elapsedString .= $days . " days, ";
        }
        if ($timePassed > 3600) {
            $hours = floor($timePassed / 3600);
            $timePassed -= $hours * 3600;
            $elapsedString .= $hours . " hours, ";
        }
        if ($timePassed > 60) {
            $minutes = floor($timePassed / 60);
            $timePassed -= $minutes * 60;
            $elapsedString .= $minutes . " minutes, ";
        }
        $elapsedString .= $timePassed . " seconds";

        return $elapsedString;
    }


    /**
     * datediff
     * Calculates the difference between given dates/timestamps
     *
     * @param string $interval         Type of differenge (e.g. years, months, weeks...)
     * @param string $datefrom         Start date
     * @param string $dateto           End Date
     * @param bool   $using_timestamps Whether the provided dates are timestamps
     *
     * @return string Human readable time difference.
     *
     * @access public
     * @static
     */
    public static function datediff($interval, $datefrom, $dateto, $using_timestamps = true)
    {
        /*
          $interval can be:
          yyyy - Number of full years
          q - Number of full quarters
          m - Number of full months
          y - Difference between day numbers
          (eg 1st Jan 2004 is "1", the first day. 2nd Feb 2003 is "33". The datediff is "-32".)
          d - Number of full days
          w - Number of full weekdays
          ww - Number of full weeks
          h - Number of full hours
          n - Number of full minutes
          s - Number of full seconds (default)
          */

        if (!$using_timestamps) {
            $datefrom = strtotime($datefrom, 0);
            $dateto   = strtotime($dateto, 0);
        }
        $difference = $dateto - $datefrom; // Difference in seconds

        switch ($interval) {
            case 'yyyy': // Number of full years
                $years_difference = floor($difference / 31536000);
                if (mktime(
                    date("H", $datefrom), date("i", $datefrom), date("s", $datefrom), date("n", $datefrom),
                    date("j", $datefrom), date("Y", $datefrom) + $years_difference
                ) > $dateto
                ) {
                    $years_difference--;
                }
                if (mktime(
                    date("H", $dateto), date("i", $dateto), date("s", $dateto), date("n", $dateto), date("j", $dateto),
                    date("Y", $dateto) - ($years_difference + 1)
                ) > $datefrom
                ) {
                    $years_difference++;
                }
                $datediff = $years_difference;
                break;

            case "q": // Number of full quarters
                $quarters_difference = floor($difference / 8035200);
                while (mktime(
                    date("H", $datefrom), date("i", $datefrom), date("s", $datefrom),
                    date("n", $datefrom) + ($quarters_difference * 3), date("j", $dateto), date("Y", $datefrom)
                ) < $dateto) {
                    $quarters_difference++;
                }
                $quarters_difference--;
                $datediff = $quarters_difference;
                break;

            case "m": // Number of full months
                $months_difference = floor($difference / 2678400);
                while (mktime(
                    date("H", $datefrom), date("i", $datefrom), date("s", $datefrom),
                    date("n", $datefrom) + ($months_difference), date("j", $dateto), date("Y", $datefrom)
                ) < $dateto) {
                    $months_difference++;
                }
                $months_difference--;
                $datediff = $months_difference;
                break;

            case 'y': // Difference between day numbers
                $datediff = date("z", $dateto) - date("z", $datefrom);
                break;

            case "d": // Number of full days
                $datediff = floor($difference / 86400);
                break;

            case "w": // Number of full weekdays
                $days_difference  = floor($difference / 86400);
                $weeks_difference = floor($days_difference / 7); // Complete weeks
                $first_day        = date("w", $datefrom);
                $days_remainder   = floor($days_difference % 7);
                $odd_days         = $first_day + $days_remainder; // Do we have a Saturday or Sunday in the remainder?
                if ($odd_days > 7) { // Sunday
                    $days_remainder--;
                }
                if ($odd_days > 6) { // Saturday
                    $days_remainder--;
                }
                $datediff = ($weeks_difference * 5) + $days_remainder;
                break;

            case "ww": // Number of full weeks
                $datediff = floor($difference / 604800);
                break;

            case "h": // Number of full hours
                $datediff = floor($difference / 3600);
                break;

            case "n": // Number of full minutes
                $datediff = floor($difference / 60);
                break;

            default: // Number of full seconds (default)
                $datediff = $difference;
                break;
        }

        return $datediff;
    }

    static public function php5BinaryLinkExists()
    {
        return (file_exists(self::PHP5_BINARY_SYMLINK) && is_link(self::PHP5_BINARY_SYMLINK));
    }

    /**
     * This method determines do we need to start a PHP session.
     * PHP sessions are only in use when working via HTTP protocol. In the CLI mode
     * we don't need to start PHP sessions (at least because of it is senseless)
     *
     * @static
     * @access public
     * @return bool
     */
    final static public function isSessionInitRequired()
    {
        return !empty($_SERVER['REQUEST_METHOD']);
    }
    
    final static public function isWindows()
    {
        return stripos(PHP_OS, 'win') === 0;
    }
}
