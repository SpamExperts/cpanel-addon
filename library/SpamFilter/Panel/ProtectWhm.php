<?php
/**
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
* @since     2.5
*/

class SpamFilter_Panel_ProtectWhm extends SpamFilter_Panel_Protect
{

    /**
     * Protect account
     *
     * @access protected
     * @var SpamFilter_Panel_Account
     */
    protected $_account;

    /**
     * Protect user container
     *
     * @access protected
     * @var string
     */
    protected $_user;

    /**
     * Protect domain container
     *
     * @access protected
     * @var string
     */
    protected $_domain;

    /**
     * Account setter
     *
     * @access public
     * @param object SpamFilter_Panel_Account $account
     * @return void
     */
    public function setAccount( $account )
    {
       $this->_account = $account;
       $this->_domain = $account->getDomain();
       $this->_user = $account->getUser();
       $this->_password = $account->getPassword();
       $this->_owner_domain = $account->getRouter();
    }

    /**
     * Domain protected method
     *
     * @param string $destination Value of destination host
     * @param SpamFilter_PanelSupport_Cpanel $panelDriver
     *
     * @access public
     * @return array
     */
    public function domainProtectHandler($destination, SpamFilter_PanelSupport_Cpanel $panelDriver)
	{
        if (!$this->_account->isValid()) {
            $this->_logger->debug("[WHM] Root domain skipped (empty/invalid/remote): '" . $this->_domain . "'.");
            $this->addDomainReason($this->_domain, $this->_account->getErrorCode());
            $this->countsUp(SpamFilter_Panel_Protect::COUNTS_FAILED);
        } else {
            if ($this->_config->bulk_change_routing && $this->_config->handle_only_localdomains) {
                $this->_account->checkMailRoutes();
            }

            $this->_logger->debug("[WHM] Requesting addition of normal domain: '" . $this->_domain . "'");

            // Try to find the domain's aliases
            // @see https://trac.spamexperts.com/software/ticket/13043
            $aliases = array();
            if ($this->_config->add_extra_alias) {

                $this->_logger->debug("[WHM] Actual settings require the add-on to add account domain's addon domains as aliases. Let's find them.");
                $domainOwnerUsername = $panelDriver->getDomainUser($this->_domain);

                $addonDomains = $panelDriver->getAddonDomains($domainOwnerUsername, $this->_domain);
                $this->_logger->debug("[WHM] The panel driver returned the following as addon domains: " . print_r($addonDomains, true));

                $parkedDomains = $panelDriver->getParkedDomains($domainOwnerUsername);
                $this->_logger->debug("[WHM] The panel driver returned the following as parked domains: " . print_r($parkedDomains, true));

                // add_extra_alias says we should add subdomains as addons too
                $subDomains = $panelDriver->getSubDomains($domainOwnerUsername);
                $this->_logger->debug("[WHM] The panel driver returned the following as parked domains: " . print_r($subDomains, true));

                $secondaryDomains = array();

                if (is_array($addonDomains)) {
                    $secondaryDomains = array_merge_recursive($secondaryDomains, $addonDomains);
                }
                if (is_array($parkedDomains)) {
                    $secondaryDomains = array_merge_recursive($secondaryDomains, $parkedDomains);
                }
                if (is_array($subDomains)) {
                    $secondaryDomains = array_merge_recursive($secondaryDomains, $subDomains);
                }

                foreach ($secondaryDomains as $data) {
                    $aliases[] = $data['alias'];
                }

                $aliases = array_unique($aliases, SORT_REGULAR);
                $this->_logger->debug("[WHM] The following data - " . print_r($aliases, true) . " - will be added as {$this->_domain}'s aliases");
            }

            $return = $this->_hook->AddDomain($this->_domain, null, true, $destination, $aliases);

            if (isset($return) && !is_array($return)) {
                $this->countsUp(self::COUNTS_FAILED);
                $this->_logger->debug("[WHM] Domain '{$this->_domain}' produced wrong return values in the process.");
                $this->addDomainReason($this->_domain, 'FAIL');
            } elseif ($return['status'] == false
                && in_array($return['reason'], array(SpamFilter_Hooks::ALIAS_EXISTS, SpamFilter_Hooks::DOMAIN_EXISTS, SpamFilter_Hooks::SKIP_ALREADYEXISTS))
            ) {
                // Only do this when it has been completed successfully, it is a DOMAIN not an alias
                if ($this->_config->provision_dns && $this->_config->bulk_force_change) {
                    // Domain already exists, but update the route + mx records please.
                    $status = $this->_hook->updateData($this->_domain, $destination);

                    /**
                     * We should try to update MX records in any way
                     * @see https://trac.spamexperts.com/software/ticket/15889
                     */
                    if (empty($status)) {
                        SpamFilter_DNS::ConfigureDNS($this->_domain, $this->_config);
                        $status = true;
                    }

                    $data_updated = $this->_hook->isDataUpdated($status,
                        array('updated' => "[WHM-BULK] Domain '{$this->_domain}' already exists but route & MX has been updated.",
                            'skipped' => "[WHM-BULK] Domain '{$this->_domain}' already exists but route/mx change has failed."));
                    $this->countsUp($data_updated['action']);
                    $this->addDomainReason($this->_domain, $data_updated['reason']);
                } else {
                    // Domain already exists, this is just a notice though.
                    $this->countsUp(self::COUNTS_SKIPPED);
                    $this->addDomainReason($this->_domain, SpamFilter_Hooks::SKIP_ALREADYEXISTS);

                    $this->_logger->debug("[WHM-BULK] Domain domain '{$this->_domain}' has been skipped because it was added already.");
                }
            } elseif ($return['status'] == false && $return['reason'] == SpamFilter_Hooks::DOMAIN_LIMIT_REACHED) {
                $this->countsUp(self::COUNTS_FAILED);
                $this->addDomainReason($this->_domain, SpamFilter_Hooks::DOMAIN_LIMIT_REACHED);
                $this->_logger->debug("[WHM] Domain '{$this->_domain}' has NOT been added because domain limit was reached.");
            } elseif ($return['status'] == false && $return['reason'] == SpamFilter_Hooks::DOMAIN_HAS_FEATURE_DISABLED) {
                $this->countsUp(self::COUNTS_FAILED);
                $this->addDomainReason($this->_domain, SpamFilter_Hooks::DOMAIN_HAS_FEATURE_DISABLED);
                $this->_logger->debug("[WHM] Domain '{$this->_domain}' has NOT been added because domain has prospamfilter feature disabled.");
            } elseif ($return['status'] == false) {
                $this->countsUp(self::COUNTS_FAILED);
                $this->_logger->debug("[WHM] Domain '{$this->_domain}' has NOT been added due to an API error ({$return['reason']}).");
                $this->addDomainReason($this->_domain, SpamFilter_Hooks::SKIP_APIFAIL);
            } else {
                // Domain added!.
                $this->countsUp(self::COUNTS_OK);
                $this->countsUp(self::COUNTS_NORMAL);
                $this->_logger->debug("[WHM] Domain '{$this->_domain}' has been added.");
                $this->addDomainReason($this->_domain, 'OK');
            }
        }

	}

    /**
     * Parked domain protected method
     *
     * @param array  of parked domain params
     * @param string Value of destination host
     *
     * @access public
     * @return array
     */
    public function parkedDomainProtectHandler($parked, $destination)
    {
        $return = null;

        /**
         * @var $panel SpamFilter_PanelSupport_Cpanel
         */
        $panel = new SpamFilter_PanelSupport();
        // Check if this PARKED DOMAIN is a remote domain (and then skip it)
        if ($this->_config->handle_only_localdomains) {
            if ($panel->IsRemoteDomain(array('domain'       => $parked, 'user' => $this->_account->getOwnerUser(),
                                             'owner_domain' => $this->_account->getRouter()))
            ) {
                $this->_logger->debug("[WHM] Filtering remote parked domain '{$parked}'");
                $this->countsUp(self::COUNTS_SKIPPED);
                $this->addDomainReason($parked, SpamFilter_Hooks::SKIP_REMOTE);

                return; // We want to SKIP this, since a parked cannot have subsequent parked domains attached its no problem to continue and proceed here.
            }
        }

        // Validate domainname
        if (SpamFilter_Core::validateDomain($parked) == false) {
            $this->_logger->debug("[WHM] Filtering invalid domain value '{$parked}' for parked domain");

            // Hmm, domain is invalid. Shouldn't be happening. Let's skip this one then.
            $this->countsUp(self::COUNTS_SKIPPED);
            $this->addDomainReason($parked, SpamFilter_Hooks::SKIP_INVALID);

            return;
        }

        // Check add type
        if (0 < $this->_config->add_extra_alias) {
            if (!$this->_account->isValid() && (SpamFilter_Hooks::SKIP_REMOTE != $this->_account->getErrorCode())) {
                // Root domain cannot be added, we cannot add it as an ALIAS then.
                $this->countsUp(self::COUNTS_SKIPPED);
                $this->addDomainReason($this->_domain, SpamFilter_Hooks::SKIP_NOROOT);

                return; //proceed with the next parked
            }

            // Else... Add as alias
            $this->_logger->debug("[WHM] Adding parked domain '{$parked}' as an alias of '" . $this->_domain . "'.");
            $return = $this->_hook->AddAlias($this->_owner_domain, $parked, true, null);
            $keyvalue = $return['reason'];
        } else {
            // Add as REAL domain
            $this->_logger->debug("[WHM] Adding parked domain '{$parked}' as a real domain.");
            $return = $this->_hook->AddDomain($parked, null, true, $destination);
            $keyvalue = "OK";
        }

        // Modify mail routing
        if ($this->_config->bulk_change_routing && ($this->_config->handle_only_localdomains)) {
            $this->_logger->debug("[WHM] Checking mail routing setting for parked domain '{$parked}'.");
            // Check it first.
            $data = $panel->GetMXmode(array('domain' => $parked, 'user' => $this->_user));
            if (isset($data) && (!empty($data))) {
                $this->_logger->debug("[WHM] Retrieved mail routing setting for parked domain '{$parked}', is currently set to: '{$data}'.");
                if (strtolower($data) == "auto"
                    && !$panel->IsRemoteDomain(array('domain'       => $parked,
                                                     'user'         => $this->_account->getOwnerUser(),
                                                     'owner_domain' => $this->_account->getRouter()))
                ) // Only if it is AUTO.
                {
                    // Change the mail acceptance to local to make sure this works
                    $this->_logger->debug("[WHM] Changing mail routing for parked domain '{$parked}' (was: '{$data}')");
                    $panel->SwitchMXmode(array('domain' => $parked,
                                                     'mode'   => 'local',));
                } else {
                    // No need, it is already set to accept email / locally.
                    $this->_logger->debug("[WHM] Parked domain '{$parked}' does not need a change to its mail routing settings (set to: '{$data}')");
                }
            } else {
                $this->_logger->debug("[WHM] Retrieval of mail routing setting for parked domain '{$parked}' has failed (Data not set). ");
            }
            // End mail routing change
        }

        if (isset($return) && !is_array($return)) {
            $this->countsUp(self::COUNTS_FAILED);
            $this->_logger->debug("[WHM] Parked domain '{$parked}' produced wrong return values in the process.");
            $this->addDomainReason($parked, 'FAIL');
        } elseif ($return['status'] == false
            && in_array($return['reason'], array(SpamFilter_Hooks::ALIAS_EXISTS, SpamFilter_Hooks::DOMAIN_EXISTS,
                SpamFilter_Hooks::SKIP_ALREADYEXISTS,
                SpamFilter_Hooks::ALREADYEXISTS_ROUTESET, SpamFilter_Hooks::ALREADYEXISTS_ROUTESETFAIL))
        ) {
            // Only do this when it has been completed succesfully, it is a DOMAIN not an alias
            if ($this->_config->provision_dns && $this->_config->bulk_force_change) {
                // Domain already exists, but update the route + mx records please.
                $status = $this->_hook->updateData($parked, $destination);

                /**
                 * We should try to update MX records in any way
                 * @see https://trac.spamexperts.com/software/ticket/15889
                 */
                if (empty($status)) {
                    SpamFilter_DNS::ConfigureDNS($parked, $this->_config);
                    $status = true;
                }

                $data_updated = $this->_hook->isDataUpdated($status,
                    array('updated' => "[WHM-BULK] Parked domain '{$parked}' already exists but route & MX has been updated.",
                          'skipped' => "[WHM-BULK] Parked domain '{$parked}' already exists but route/mx change has failed."));
                $this->countsUp($data_updated['action']);
                $this->addDomainReason($parked, $data_updated['reason']);
            } else {
                // Domain already exists, this is just a notice though.
                $this->countsUp(self::COUNTS_SKIPPED);
                $this->addDomainReason($parked, SpamFilter_Hooks::SKIP_ALREADYEXISTS);

                $this->_logger->debug("[WHM-BULK] Parked domain '{$parked}' has been skipped because it was added already.");
            }
        } elseif ($return['status'] == false && $return['reason'] == SpamFilter_Hooks::DOMAIN_LIMIT_REACHED) {
            $this->countsUp(self::COUNTS_FAILED);
            $this->addDomainReason($parked, SpamFilter_Hooks::DOMAIN_LIMIT_REACHED);
            $this->_logger->debug("[WHM] Parked domain '{$parked}' has NOT been added because domain limit was reached.");
        } elseif ($return['status'] == false && $return['reason'] == SpamFilter_Hooks::DOMAIN_HAS_FEATURE_DISABLED) {
            $this->countsUp(self::COUNTS_FAILED);
            $this->addDomainReason($parked, SpamFilter_Hooks::DOMAIN_HAS_FEATURE_DISABLED);
            $this->_logger->debug("[WHM] Domain '{$parked}' has NOT been added because domain has prospamfilter feature disabled.");
        } elseif ($return['status'] == false) {
            $this->countsUp(self::COUNTS_FAILED);
            $this->_logger->debug("[WHM] Parked domain '{$parked}' has NOT been added due to an API error ({$return['reason']}).");
            $this->addDomainReason($parked, SpamFilter_Hooks::SKIP_APIFAIL);
        } else {
            // Addon added!.
            $this->countsUp(self::COUNTS_OK);
            $this->countsUp(self::COUNTS_PARKED);
            $this->_logger->debug("[WHM] Parked domain '{$parked}' has been added.");
            $this->addDomainReason($parked, $keyvalue);
        }
    }

    /**
     * Addon domain protected method
     *
     * @param array of addon domain params
     * @param string Value of destination host
     * @param array of result
     *
     * @access public
     * @return array
     */
	public function addonDomainProtectHandler ($addon, $destination)
	{
		$return = null;
        /** @var $panel SpamFilter_PanelSupport_Cpanel */
		$panel = new SpamFilter_PanelSupport();
		// Check if this ADDON DOMAIN is a remote domain (and then skip it)
		if( $this->_config->handle_only_localdomains )
		{
			if ( $panel->IsRemoteDomain( array('domain' => $addon, 'user' => $this->_account->getOwnerUser(), 'owner_domain' => $this->_account->getRouter() ) )) // Skip _remote_ domains only.
			{
				$this->_logger->debug("[WHM] Filtering remote addon domain '{$addon}'");
				$this->countsUp(self::COUNTS_SKIPPED);
				$this->addDomainReason($addon, SpamFilter_Hooks::SKIP_REMOTE);
				return;
			}
		}

		// Validate domainname
		if ( SpamFilter_Core::validateDomain($addon) == false)
		{
			$this->_logger->debug("[WHM] Filtering invalid domain value for addon domain '{$addon}'");

			// Hmm, domain is invalid. Shouldn't be happening. Let's skip this one then.
			$this->countsUp(self::COUNTS_SKIPPED);
			$this->addDomainReason($addon, SpamFilter_Hooks::SKIP_INVALID);
			return;
		}

		// Check add type
		if( $this->_config->add_extra_alias )
		{
			if (!$this->_account->isValid()) {
				// Root domain cannot be added, we cannot add it as an ALIAS then.
				$this->countsUp(self::COUNTS_SKIPPED);
				$this->addDomainReason($this->_domain, SpamFilter_Hooks::SKIP_NOROOT);
				return; //proceed with the next addon
			}

			// Else... Add as alias
			$this->_logger->debug("[WHM] Adding addon domain '{$addon}' as an alias of '{$addon}'.");
			$return = $this->_hook->AddAlias($this->_owner_domain, $addon, true, null);
			$keyvalue = $return['reason'];
		} else {
			// Add as REAL domain
			$this->_logger->debug("[WHM] Adding addon domain '{$addon}' as a real domain.");
            $return = $this->_hook->AddDomain($addon, null, true, $destination);
			$keyvalue = "OK";
		}

		// Modify mail routing
		if( $this->_config->bulk_change_routing && ($this->_config->handle_only_localdomains) )
		{
			$this->_logger->debug("[WHM] Checking mail routing setting for addon domain '{$addon}'.");

			// Check it first.
			$data = $panel->GetMXmode( array('domain' => $addon, 'user' => $this->_account->getOwnerUser()));
			if(isset($data) && (!empty($data) ))
			{
				$this->_logger->debug("[WHM] Retrieved mail routing setting for addon domain '{$addon}', is currently set to: '{$data}'.");
				if ( strtolower($data) == "auto" && !$panel->IsRemoteDomain(array('domain' => $addon, 'user' => $this->_account->getOwnerUser(), 'owner_domain' => $this->_account->getRouter())))  // Only if it is AUTO.
				{
					// The routing is NOT set to "local" (probably auto) OR the alwaysaccept is set to 0.
					$this->_logger->debug("[WHM] Changing mail routing for addon domain '{$addon}' (was: '{$data}')");
					// Change the mail acceptance to local to make sure this works
					$panel->SwitchMXmode( array('domain' => $addon,
				                                      'mode' => 'local',) );
				} else {
					// No need, it is already set to accept email / locally.
					$this->_logger->debug("[WHM] Addon domain '{$addon}' does not need a change to its mail routing settings (set to: '{$data}')");
				}
			} else {
				$this->_logger->debug("[WHM] Retrieval of mail routing setting for addon domain '{$addon}' has failed (Data not set). ");
			}
		}
		// End mail routing change

		if ( (isset($return)) && (!is_array($return)) )
		{
			$this->countsUp(self::COUNTS_FAILED);
			$this->_logger->debug("[WHM] Addon domain '{$addon}' produced wrong return values in the process");
			$this->addDomainReason($addon, 'FAIL');
		}
		elseif ( $return['status'] == false && ($return['reason'] == SpamFilter_Hooks::ALIAS_EXISTS
		                                     || $return['reason'] == SpamFilter_Hooks::DOMAIN_EXISTS
		                                     || $return['reason'] == SpamFilter_Hooks::SKIP_ALREADYEXISTS
		                                     || $return['reason'] == SpamFilter_Hooks::ALREADYEXISTS_ROUTESET
		                                     || $return['reason'] == SpamFilter_Hooks::ALREADYEXISTS_ROUTESETFAIL) )
		{
			// Only do this when it has been completed succesfully, it is a DOMAIN not alias
			if( $this->_config->provision_dns && $this->_config->bulk_force_change)
			{
				// Domain already exists, but update the route + mx records please.
				$status = $this->_hook->updateData( $addon, $destination);

                /**
                 * We should try to update MX records in any way
                 * @see https://trac.spamexperts.com/software/ticket/15889
                 */
                if (empty($status['status'])) {
                    SpamFilter_DNS::ConfigureDNS($addon, $this->_config);
                    $status = true;
                }

                $data_updated = $this->_hook->isDataUpdated($status, array ('updated' => "[WHM-BULK] Addon domain '{$addon}' already exists but route & MX has been updated.",
								                            'skipped' => "[WHM-BULK] Addon domain '{$addon}' already exists but route/mx change has failed."));
				$this->countsUp($data_updated['action']);
				$this->addDomainReason($addon, $data_updated['reason']);
			} else {
				// Domain already exists, this is just a notice though.
				$this->countsUp(self::COUNTS_SKIPPED);
				$this->addDomainReason($addon, SpamFilter_Hooks::SKIP_ALREADYEXISTS);

				$this->_logger->debug("[WHM-BULK] Addon domain '{$addon}' has been skipped because it was added already.");
			}
        } elseif ($return['status'] == false && $return['reason'] == SpamFilter_Hooks::DOMAIN_LIMIT_REACHED) {
            $this->countsUp(self::COUNTS_FAILED);
            $this->addDomainReason($addon, SpamFilter_Hooks::DOMAIN_LIMIT_REACHED);
            $this->_logger->debug("[WHM] Addon domain '{$addon}' has NOT been added because domain limit was reached.");
        } elseif ($return['status'] == false && $return['reason'] == SpamFilter_Hooks::DOMAIN_HAS_FEATURE_DISABLED) {
            $this->countsUp(self::COUNTS_FAILED);
            $this->addDomainReason($addon, SpamFilter_Hooks::DOMAIN_HAS_FEATURE_DISABLED);
            $this->_logger->debug("[WHM] Addon '{$addon}' has NOT been added because domain has prospamfilter feature disabled.");
        } elseif ($return['status'] == false )
		{
			$this->countsUp(self::COUNTS_FAILED);
			$this->_logger->debug("[WHM] Addon domain '{$addon}' has NOT been added due to an API error ({$return['reason']}).");
			$this->addDomainReason($addon, SpamFilter_Hooks::SKIP_APIFAIL);
		} else {
			// Addon added!.
			$this->countsUp(self::COUNTS_OK);
			$this->countsUp(self::COUNTS_ADDON);
			$this->_logger->debug("[WHM] Addon domain '{$addon}' has been added.");
			$this->addDomainReason($addon, $keyvalue);
		}
	}

    /**
     * Subdomain protection method
     *
     * @param string $sub - name of subdomain
     * @param string Value of destination host
     *
     * @access public
     * @return array
     */
    public function subDomainProtectHandler ($sub, $destination)
    {
        $return = null;
        /** @var $panel SpamFilter_PanelSupport_Cpanel */
        $panel = new SpamFilter_PanelSupport();
        // Check if this SUBDOMAIN is a remote domain (and then skip it)
        if( $this->_config->handle_only_localdomains )
        {
            if ( $panel->IsRemoteDomain( array('domain' => $sub, 'user' => $this->_account->getOwnerUser(), 'owner_domain' => $this->_account->getRouter() ) )) // Skip _remote_ domains only.
            {
                $this->_logger->debug("[WHM] Filtering remote subdomain '{$sub}'");
                $this->countsUp(self::COUNTS_SKIPPED);
                $this->addDomainReason($sub, SpamFilter_Hooks::SKIP_REMOTE);
                return;
            }
        }

        // Validate domainname
        if ( SpamFilter_Core::validateDomain($sub) == false)
        {
            $this->_logger->debug("[WHM] Filtering invalid domain value for subdomain '{$sub}'");

            // Hmm, domain is invalid. Shouldn't be happening. Let's skip this one then.
            $this->countsUp(self::COUNTS_SKIPPED);
            $this->addDomainReason($sub, SpamFilter_Hooks::SKIP_INVALID);
            return;
        }

        // Check add type
        if( $this->_config->add_extra_alias )
        {

            if (!$this->_account->isValid()) {
                // Root domain cannot be added, we cannot add it as an ALIAS then.
                $this->countsUp(self::COUNTS_SKIPPED);
                $this->addDomainReason($this->_domain, SpamFilter_Hooks::SKIP_NOROOT);
                return; //proceed with the next subdomain
            }
            // Else... Add as alias
            $this->_logger->debug("[WHM] Adding subdomain '{$sub}' as an alias of '{$this->_account->getRouter()}'.");
            $return = $this->_hook->AddAlias($this->_owner_domain, $sub, true, null);
            $keyvalue = $return['reason'];

        } else {
            if($panel->isInFilter($this->_owner_domain)){
                $SEAPI = new SpamFilter_ResellerAPI();
                $routes = $SEAPI->domain()->getroute(array('domain' => $this->_owner_domain));
                $x = explode('::', $routes[0]);
                $destination = $x[0];
            }
            // Add as REAL domain
            $this->_logger->debug("[WHM] Adding subdomain '{$sub}' as a real domain.");
            $return = $this->_hook->AddDomain($sub, null, true, $destination);
            $keyvalue = "OK";
        }

        if ( (isset($return)) && (!is_array($return)) )
        {
            $this->countsUp(self::COUNTS_FAILED);
            $this->_logger->debug("[WHM] Subdomain '{$sub}' produced wrong return values in the process");
            $this->addDomainReason($sub, 'FAIL');
        }
		elseif ( $return['status'] == false && ($return['reason'] == SpamFilter_Hooks::ALIAS_EXISTS
		                                     || $return['reason'] == SpamFilter_Hooks::DOMAIN_EXISTS
		                                     || $return['reason'] == SpamFilter_Hooks::SKIP_ALREADYEXISTS
		                                     || $return['reason'] == SpamFilter_Hooks::ALREADYEXISTS_ROUTESET
		                                     || $return['reason'] == SpamFilter_Hooks::ALREADYEXISTS_ROUTESETFAIL) )
		{
			// Only do this when it has been completed succesfully, it is a DOMAIN not alias
			if( $this->_config->provision_dns && $this->_config->bulk_force_change)
			{
				// Domain already exists, but update the route + mx records please.
				$status = $this->_hook->updateData( $sub, $destination);

                /**
                 * We should try to update MX records in any way
                 * @see https://trac.spamexperts.com/software/ticket/15889
                 */
                if (empty($status['status'])) {
                    SpamFilter_DNS::ConfigureDNS($sub, $this->_config);
                    $status = true;
                }

                $data_updated = $this->_hook->isDataUpdated($status, array ('updated' => "[WHM-BULK] Addon domain '{$sub}' already exists but route & MX has been updated.",
								                            'skipped' => "[WHM-BULK] Addon domain '{$sub}' already exists but route/mx change has failed."));
				$this->countsUp($data_updated['action']);
				$this->addDomainReason($sub, $data_updated['reason']);
			} else {
				// Domain already exists, this is just a notice though.
				$this->countsUp(self::COUNTS_SKIPPED);
				$this->addDomainReason($sub, SpamFilter_Hooks::SKIP_ALREADYEXISTS);

				$this->_logger->debug("[WHM-BULK] Addon domain '{$sub}' has been skipped because it was added already.");
			}
        } elseif ($return['status'] == false && $return['reason'] == SpamFilter_Hooks::DOMAIN_LIMIT_REACHED) {
            $this->countsUp(self::COUNTS_FAILED);
            $this->addDomainReason($sub, SpamFilter_Hooks::DOMAIN_LIMIT_REACHED);
            $this->_logger->debug("[WHM] Subdomain domain '{$sub}' has NOT been added because domain limit was reached.");
        } elseif ($return['status'] == false && $return['reason'] == SpamFilter_Hooks::DOMAIN_HAS_FEATURE_DISABLED) {
            $this->countsUp(self::COUNTS_FAILED);
            $this->addDomainReason($sub, SpamFilter_Hooks::DOMAIN_HAS_FEATURE_DISABLED);
            $this->_logger->debug("[WHM] Domain '{$sub}' has NOT been added because domain has prospamfilter feature disabled.");
		} elseif ($return['status'] == false ) {
            $this->countsUp(self::COUNTS_FAILED);
            $this->_logger->debug("[WHM] Subdomain '{$sub}' has NOT been added due to an API error ({$return['reason']}).");
            $this->addDomainReason($sub, SpamFilter_Hooks::SKIP_APIFAIL);
        } else {
            // Subdomain added!.
            $this->countsUp(self::COUNTS_OK);
            $this->countsUp(self::COUNTS_SUBDOMAIN);
            $this->_logger->debug("[WHM] Subdomain '{$sub}' has been added.");
            $this->addDomainReason($sub, $keyvalue);
        }
    }

}
