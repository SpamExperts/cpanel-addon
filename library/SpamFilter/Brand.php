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

/*
* SpamFilter Branding
*
* This class provides branding options for the addon (as long as the panel supports it)
*
* @class     SpamFilter_Brand
* @category  SpamExperts
* @package   ProSpamFilter
* @author    $Author$
* @copyright Copyright (c) 2011, SpamExperts B.V., All rights Reserved. (http://www.spamexperts.com)
* @license   Closed Source
* @version   3.0
* @link      https://my.spamexperts.com/kb/34/Addons
* @since     2.0
*/
class SpamFilter_Brand
{
    const ICON_PATH_PLESK = '/usr/local/psa/admin/htdocs/images/custom_buttons/prospamfilter.png';

	private $_configData;
	private $_configFile;
	private $_productList;

	public function __construct( )
	{
        if(!CFG_PATH) {
			throw new Exception("Unable to load branding configuration without a provided config path");
		}

		$this->_configFile = CFG_PATH . DS . 'branding.conf';
        // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.FilesystemFunctions.WarnFilesystem
		if( !is_readable( $this->_configFile ) ) {
			Zend_Registry::get('logger')->err("[Brand] Cannot read my configfile. ({$this->_configFile})");
			return false;
		}

		Zend_Registry::get('logger')->debug("[Branding] Retrieving configuration from '{$this->_configFile}'");
		$this->_configData = $this->_getIniContent( $this->_configFile );

		if( $this->_configData ) {
			Zend_Registry::get('logger')->err("[Brand] Branding configuration loaded.");
			Zend_Registry::set('branding', $this->_configData ); // Optional, no real need for it though.
			return true;
		}

		Zend_Registry::get('logger')->err("[Brand] Unable to obtain branding configuration.");
		return false;
	}

    public function hasBrandingData()
    {
        return $this->_configData ? true : false;
    }

	private function _getIniContent( $fileName )
	{
		// Used to obtain the normal ini content
		try
		{
			Zend_Registry::get('logger')->debug("[Brand] Loading branding config from '{$fileName}'");
			$config = new Zend_Config_Ini( $fileName );
		}
		catch(Zend_Config_Exception $e)
		{
			Zend_Registry::get('logger')->crit("[Brand] Failed to load the INI config. ({$e->getMessage()})");
			return false;
		}

		// Check if it is set.
		if( $config )
		{
			Zend_Registry::get('logger')->debug("[Brand] Data is set, saving to registry");
			return $config;
		}

		Zend_Registry::get('logger')->err("[Brand] Loading branding configuration has failed.");
		return false;
	}

	/**
	 * Retrieve the current brand
	 * @return string Current brand used
	 *
	 * @access public
	 */
	public function getBrandUsed()
	{
        $hasWhitelabel = $this->hasWhitelabel();
		if ( (isset($this->_configData->brandname)) && (!empty($this->_configData->brandname)) && $hasWhitelabel)
		{
			Zend_Registry::get('logger')->debug("[Brand] Brand is set, returning value '{$this->_configData->brandname}'...");
			return $this->_configData->brandname;
		}

		// Fallback
		Zend_Registry::get('logger')->debug("[Brand] No local brand configured (or WL disabled), falling back to default.");
		return $this->getDefaultBrandname();
	}

	/**
	 * Check whether the cluster has a whitelabel license.
	 *
	 * @return bool Whitelabel licence enabled/disabled
	 *
	 * @access public
	 */
	public function hasWhitelabel()
	{
        if (empty($this->_productList)) {
            // Do an API call
            $api = new SpamFilter_ResellerAPI();
            if(!$api) {
                // Unable to check
                Zend_Registry::get('logger')->err("[Brand] Unable to check for whitelabel without API access.");
                return false;
            } else {
				// Do get_products
                $this->_productList = $api->productslist()->get( array() );
            }
        }
        if (empty($this->_productList)) {
            // Unable to retrieve
            Zend_Registry::get('logger')->err("[Brand] Unable to retrieve API methods. Maybe 'api/productslist' is disabled?");
            return false;
        }
		// Methods received, check if we have the allowed methods.
		if (is_array($this->_productList) &&
			(!isset($this->_productList['reason']) || $this->_productList['reason'] != 'API_REQUEST_FAILED')) {
            if(in_array('whitelabel', $this->_productList)) {
				Zend_Registry::get('logger')->debug("[Brand] Whitelabel is enabled.");
				return true;
			}
		}

		if (PHP_SAPI !== 'cli' && is_array($this->_productList) &&
			isset($this->_productList['reason']) && $this->_productList['reason'] == 'API_REQUEST_FAILED') {
            $message = array(
                'message' => "Unable to communicate with the Spamfilter API. Please check the configuration and if the problem persists contact your administrator or service provider.",
                'status' => 'error'
            );
			$messageQueue = new SpamFilter_Controller_Action_Helper_FlashMessenger();
            $messageQueue->addMessage($message);
        }

		// Did not find it, so not enabled.
		Zend_Registry::get('logger')->debug("[Brand] Whitelabel has NOT been enabled (or return value not correct).");
		return false;
	}


	/**
	 * Retrieve the default brandname
	 *
	 * @return string Brandname
	 *
	 * @access public
	 */
	static public function getDefaultBrandname()
	{
		return "Professional Spam Filter";
	}

	/**
	 * Retrieve the default brand icon
	 *
	 * @return string Base64 encoded icon
	 *
	 * @access public
	 */
	static public function getDefaultIcon()
	{
		// Image is set to be /bin/cpanel/paper_lantern/psf_button/se-logo.png which is 40x40
		// @see https://trac.spamexperts.com/ticket/25117
		return "iVBORw0KGgoAAAANSUhEUgAAADAAAAAwCAYAAABXAvmHAAAACXBIWXMAAAA7AAAAOwG4ag7yAAADhUlEQVRoge1Z223bMBS9Lfpfffk7hQaoPEHjCWJOUHuCuBM4mSD1BHImkDOBlAnsDiAg3/qpukFxg0PjmuZLsoIggA4QwFZE8hzy3sNLmkaMGDHiXfFpqMEnRT0noh9ElBHRteO1AxFVRPTcqHQ3xLgXCZgU9RURLYjoloiSjs1bItoQ0bZR6UtfDr0FTIr6ridxE69CGpXe9WncWQBmvUCo+EgdjGdZQCy/r7quRicBk6JmEqWDCBN4JKKdiwTEz7Fy/NkEC581KjXFXy7AQ56T8r5RaRXbF/rj3FlbhHQSESUAM7e3kP/VqPR3NOvzfrm/BxiBBIuYxoTT58ixCoO8HqA3eUaj0rZR6ZInwvhXgjGDCAqA28iE7RynEUJ4Iu6NxxnG9sIbQo7QWTYq3Q5F3hivQJJrBEMptAILS9zfXMzUAuSDmdCJJT9OEBJwKz5rb59PijofWgAR5QjV1sPhDE4BqG3k7HPtssQAC9jgIECsz9H3N0NEAi7dBKAwk9ggcZd6xiZF7SraoiH2A4I5MHkzx0wuUQIkuRftOqgite0V2OD6ks+wDxDMQTvbk4fLCXwCJLETy4TtbRFiORKwK3nt9Qkq0uOsW3Z15yTFbmR/zAfYgA7ovOxCHuA27DoH9GUiap+JFeDCDAmXdXEmvKsdZ+Z4zXQjKy4VIJ2KnWkVaoB3pIPZqtJo9BaABMyRCzoEHnyWB9c6Ji2suXQYQVRexQr4biFfIvl0aaFrmdxGCM90gfaatIh9l4god/MJkEl07EyQ38nkw5Fwh5krpTPhc47/VUa7MxEWMc6zxhePgEoQv0Jhl9jICywR0xkI6QQt8YyLMmU24r7QP7eZGgUd+RzJtwLPxvc1iFQO8oRdVJcbTPgv/rTjKLxjg4JADrOfAS5hAdhx5WALDGAlL9odYI1y2avQGQLC9IpJZ2p9d0i+ECLc26zF9yfPDNpEdAL3bdnVN74+Qi60NVZhfUntEwKq0pPZtxR28QJwEjJnoOxT+0SQXxirTaiAvQf72FuJvaW463wJ5en/2nJxwDXSNNQ2diNTRiixmP1A54GV5b6ptdmtDUNcbG1xPxRVfIn+ONZzS60//MWWGNQlosVuugkNjFrpxnFYf7urRUEgdLmrD//89w/PvuJ93wXv21/uSnzY63UJ2OnqQ/7AYSLyJ6YKoTLYT0wjRowY8Y4gov9zhIbXIDqmsAAAAABJRU5ErkJggg==";
	}

	/**
	 * Retrieve current set brand icon (if the panel supports it)
	 *
	 * @return string Base64 encoded icon
	 *
	 * @access public
	 */
	public function getBrandIcon($force = false)
	{
                $wl = true;
		if(!$force){
                    // We don't want to execute this method while is forced to get icon
                    $wl = $this->hasWhitelabel();
                }
                if ( (isset($this->_configData->brandicon)) && (!empty($this->_configData->brandicon)) && $wl )
		{
			Zend_Registry::get('logger')->debug("[Brand] Brand icon is set, returning...");
			return $this->_configData->brandicon;
		}

		// Default value.
		Zend_Registry::get('logger')->debug("[Brand] getBrandIcon returns default value");
		return $this->getDefaultIcon();

	}

	/**
	 * Set the brandname
	 *
	 * @param string $brandname Brandname to set
	 *
	 * @see getPanelType()
	 * @see hasWhitelabel()
	 * @see setBrandName()
	 *
	 * @return bool Status
	 *
	 * @access public
	 */
	public function updateBrandname( $brandname = null )
	{
		if(!empty($brandname))
		{
			Zend_Registry::get('logger')->info("[Brand] Updating brandname to '{$brandname}'");
		}

		// Perform panel specific actions (if required) to rebrand the addon.
		if(empty($brandname))
		{
			// get it!
			$brandname = $this->getBrandUsed();
			Zend_Registry::get('logger')->info("[Brand] Updating brandname to '{$brandname}'");
		}
		$paneltype = strtolower( SpamFilter_Core::getPanelType() );

		if( ($brandname != $this->getDefaultBrandname() ) && (!$this->hasWhitelabel()) )
		{
			Zend_Registry::get('logger')->err("[Brand] Cannot update brand to '{$brandname}': No whitelabel license available.");
			return false;
		}

		// Write to config
		if (! $this->updateOption('brandname', $brandname) )
		{
			Zend_Registry::get('logger')->err("[Brand] Cannot save branding change to configuration file.");
			return false;
		}

		Zend_Registry::get('logger')->info("[Brand] Asking {$paneltype} to update brand to: '{$brandname}'");
		switch ($paneltype)
		{
			case "whm":
			case "cpanel":
				// cPanel/WHM is one exception we have to make.
				$panel = new SpamFilter_PanelSupport( 'cpanel' );
				return $panel->setBrandname( $brandname );
			break;

			default:
				// For other panels we do not have to make an exception
				$panel = new SpamFilter_PanelSupport( );
				return $panel->setBrandname( $brandname );
			break;
		}

		Zend_Registry::get('logger')->info("[Brand] Updating brand has failed.");
		return false;
	}

	/**
	 * Set the brandname and icon in one go
	 *
	 * @param array $brandingData Brandname to set
	 *
	 * @see getPanelType()
	 * @see setBrand()
	 *
	 * @return bool Status
	 *
	 * @access public
	 */
	public function updateBranding( $brandingData )
	{
		Zend_Registry::get('logger')->info("[Brand] Updating complete branding ({$brandingData['brandname']}) including icon");
		// Write to config
		if (! $this->updateOption('brandname', trim($brandingData['brandname']) ) )
		{
			Zend_Registry::get('logger')->err("[Brand] Cannot save branding change (brandname) to configuration file.");
			return false;
		}

		if (! $this->updateOption('brandicon', trim($brandingData['brandicon'])) )
		{
			Zend_Registry::get('logger')->err("[Brand] Cannot save branding change (icon) to configuration file.");
			return false;
		}

		$paneltype = strtolower( SpamFilter_Core::getPanelType() );
		switch ($paneltype)
		{
			case "whm":
			case "cpanel":
				// cPanel/WHM is one exception we have to make.
				$panel = new SpamFilter_PanelSupport( 'cpanel' );
			break;

			case "plesk":
				// cPanel/WHM is one exception we have to make.
				$panel = new SpamFilter_PanelSupport( 'plesk' );
			break;

			default:
				// For other panels we do not have to make an exception
				$panel = new SpamFilter_PanelSupport( );
			break;
		}

		// Two calls to rule them all
		if (isset($panel)) {
			Zend_Registry::get('logger')->info("[Brand] Pushing request to Panel.");
			Zend_Registry::get('logger')->debug("[Brand] Setting Brandname: {$brandingData['brandname']}.");
			Zend_Registry::get('logger')->debug("[Brand] Setting BrandIcon: {$brandingData['brandicon']}.");

            /** @var $panel SpamFilter_PanelSupport_Cpanel */
			return $panel->setBrand(array(
                'brandname' => $brandingData['brandname'],
                'brandicon' => $brandingData['brandicon'],
            ));
		}

		Zend_Registry::get('logger')->info("[Brand] Updating branding has failed.");
                return false;
	}

/**
 * updateOption
 * Updates one specific option in the configuration
 *
 * @param $key Key to update
 * @param $value Value to set for specified key
 *
 * @return Zend_Config_Ini object|False in case it failed
 *
 * @todo Remove this piece of code and make a centralized config writer option of it.
 *
 * @access private
 * @see WriteConfig()
 */
	private function updateOption($key, $value)
	{
		Zend_Registry::get('logger')->debug("[Brand] Updating '{$key}'");
		$config = $this->_configData;
		if( (isset($config)) && (is_object($config)) )
		{
			try {
				$x = $config->toArray();
			} catch (Exception $e) {
				Zend_Registry::get('logger')->err("[Brand] Updating '{$key}' has failed.");
				return false;
			}
			// Direct key
			$x[$key] = $value;
			Zend_Registry::get('logger')->info("[Brand] Key '{$key}' has been updated.");
			return $this->WriteConfig( $x );
		}
		Zend_Registry::get('logger')->err("[Brand] Updating '$key' has failed.");
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
 * @todo Remove this piece of code and make a centralized config writer option of it.
 *
 * @access private
 */
	private function WriteConfig( $cfgData )
	{
		if(is_array($cfgData))
		{
			// Generate a clean config
			$config = new Zend_Config(array(), true);

			// Generate new values
			foreach ($cfgData as $key => $value)
			{
				$config->$key = $value;
			}
			// Write values to the INI file
			$writer = new Zend_Config_Writer_Ini(
							array(
								'config' => $config,
								'filename' => $this->_configFile
								)
							    );
			// Lets write.
			$writer->write( );

			// Write config to variable
			$this->_configData = $config;

			// Write config to registry
			Zend_Registry::set( 'branding_config', $config );


			// All done.
			return true;
		}
		return false;
	}

    public function hasAPIAccess() {
        if (!empty($this->_productList['reason'])) {
            return !in_array($this->_productList['reason'], array(
                "API_REQUEST_FAILED",
				"INVALID_API_CREDENTIALS",
                "API_USER_INACTIVE",
                "API_ACCESS_DISABLED",
                "API_IP_ACCESS_ERROR",
            ));
        }
        return true;
    }
}
