<?php
/**
*************************************************************************
*                                                                       *
* ProSpamFilter                                                         *
* Bridge between Webhosting panels & SpamExperts filtering		        *
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
* @since     2.5
*/

class SpamFilter_Panel_Account
{
    /**
     * Logger instance
     *
     * @access protected
     * @var Zend_Logger
     */
    protected $_logger;

    /**
     * Panel API instance
     *
     * @access protected
     */
    protected $_panelApi;

    /**
     * Account user container
     *
     * @access protected
     * @var string
     */
    protected $_user;

    /**
     * Account domain container
     *
     * @access protected
     * @var string
     */
    protected $_domain;

    /**
     * Account validation error code container
     *
     * @access protected
     * @var string
     */
    protected $_errorCode;

    /**
     * Panel interface
     *
     * @access protected
     * @var SpamFilter_PanelSupport_Cpanel
     */
	protected $_panel;

	/**
     * Owner domain
     *
     * @access protected
     * @var string
     */
    protected $_owner_domain;

    /**
     * Owner user of domain
     *
     * @access protected
     * @var string
     */
    protected $_owner_user;



    /**
     * Class constructor
     *
     * @access public
     * @param array $account
     * @param boolean $remoteDomainsAllowed
     * @return SpamFilter_Panel_Account
     */
    public function __construct($account, $remoteDomainsAllowed)
    {
        $this->_logger = Zend_Registry::get('logger');
        $this->_domain = (!empty($account['domain']) ? $account['domain'] : 'empty');
        $this->_owner_domain = (!empty($account['owner_domain']) ? $account['owner_domain'] : null);
        $this->_user = (!empty($account['user']) ? $account['user'] : 'empty');
        $this->_password = (!empty($account['password']) ? $account['password'] : '');
        $this->_owner_user = (!empty($account['owner_user']) ? $account['owner_user'] : null);
        $this->_panel = new SpamFilter_PanelSupport();

        // Validate domain present
        if (!isset($account['domain'])) {
            $this->_logger->debug("Filtering empty domain value.");

            // Hmm, domain is empty. Shouldn't be happening. Let's skip this one then.
            $this->_errorCode = SpamFilter_Hooks::SKIP_DATAINVALID;

            // Validate domainname
        } elseif (!SpamFilter_Core::validateDomain($account['domain'])) {
            $this->_logger->debug("Filtering invalid domain value: '{$this->_domain}'");

            // Hmm, domain is invalid. Shouldn't be happening. Let's skip this one then.
            $this->_errorCode = SpamFilter_Hooks::SKIP_INVALID;
        } elseif (!$remoteDomainsAllowed
            && !defined('SKIP_DOMAIN_REMOTENESS_CHECK')
            && $this->_panel->IsRemoteDomain(array(
                'domain' => $this->_domain,
                'user' => $this->getOwnerUser(),
                'owner_domain' => $this->getRouter(),
                'skip_deep_check' => true,
            ))
        ) {
            $this->_logger->debug("Filtering remote domain '{$this->_domain}'.");
            $this->_errorCode = SpamFilter_Hooks::SKIP_REMOTE;
        }
    }

    /**
     * An instance validity checker method
     *
     * @access public
     * @return boolean
     */
    public function isValid()
    {
        return is_null($this->_errorCode);
    }

    /**
     * Domainname getter
     *
     * @access public
     * @return string
     */
    public function getDomain()
    {
        return $this->_domain;
    }


    /**
     * Username getter
     *
     * @access public
     * @return string
     */
    public function getUser()
    {
        return $this->_user;
    }

    /**
     * User password getter
     *
     * @access public
     * @return string
     */
    public function getPassword()
    {
        return $this->_password;
    }

    /**
     * Error code getter
     *
     * @access public
     * @return string
     */
    public function getErrorCode()
    {
        return $this->_errorCode;
    }

    /**
     * router getter
     *
     * @access public
     * @return string
     */
    public function getRouter()
    {
        return $this->_owner_domain;
    }

    /**
     * Username getter
     *
     * @access public
     * @return string
     */
    public function getOwnerUser()
    {
        return $this->_owner_user;
    }


    /**
     * Mail routes checker method
     *
     * @access public
     * @return void
	 * @todo This should *only* apply to cPanel, not to any of the other panels.
     */
    public function checkMailRoutes()
    {
        $this->_logger->debug("Checking mail routing setting for '{$this->_domain}'.");

        // Check it first.
        $mxcheck = $this->_panel->GetMXmode( array('domain' => $this->_domain) );

		if (isset($mxcheck) && (!empty($mxcheck)))
		{
			$this->_logger->debug("Retrieved mail routing setting for '{$this->_domain}', is currently set to: '{$mxcheck}'.");

			if ("auto" == strtolower($mxcheck) && !$this->_panel->IsRemoteDomain( array('domain' => $this->_domain, 'user' => $this->getOwnerUser(), 'owner_domain' => $this->getRouter()))) { // Only if it is AUTO.
				// Change the mail acceptance to local to make sure this works
				$this->_logger->debug("Changing mail routing for '{$this->_domain}' (was: '{$mxcheck}')");
				$this->_panel->SwitchMXmode( array('domain' => $this->_domain,
				                                   'mode' => 'local',));
			} else {
				// No need, it is already set to accept email / locally.
				$this->_logger->debug("Domain '{$this->_domain}' does not need a change to its mail routing settings (set to: '{$mxcheck}')");
			}
		} else {
			$this->_logger->debug("Retrieval of mail routing setting for '{$this->_domain}' has failed (Data not set). ");
		}
    }
}
