<?php

/*
*************************************************************************
*                                                                       *
* ProSpamFilter                                                         *
* Bridge between Webhosting panels & SpamExperts filtering              *
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

class SpamFilter_Hooks
{
    const SKIP_DATAINVALID 				= 'SKIP_DATAINVALID';
    const SKIP_UNKNOWN 					= 'SKIP_UNKNOWN';
    const SKIP_REMOTE 					= 'SKIP_REMOTE';
    const SKIP_APIFAIL 					= 'SKIP_APIFAIL';
    const SKIP_ALREADYEXISTS 			= 'SKIP_ALREADYEXISTS';
    const SKIP_INVALID 					= 'SKIP_INVALID';
    const SKIP_NOROOT 					= 'SKIP_NOROOT';
    const ALREADYEXISTS_ROUTESET 		= 'ALREADYEXISTS_ROUTESET';
    const ALREADYEXISTS_ROUTESETFAIL 	= 'ALREADYEXISTS_ROUTESETFAIL';
    const ALREADYEXISTS_NOT_OWNER 		= 'ALREADYEXISTS_NOT_OWNER';
    const API_REQUEST_FAILED 			= 'API_REQUEST_FAILED';
    const DOMAIN_EXISTS 				= 'DOMAIN_EXISTS';
    const ALIAS_EXISTS 					= 'ALIAS_EXISTS';
    const DOMAIN_ADDED 					= 'OK';
    const DOMAIN_LIMIT_REACHED 			= 'DOMAIN_LIMIT_REACHED';
    const NO_SUCH_DOMAIN 			    = 'NO_SUCH_DOMAIN';
    const API_USER_INACTIVE 		    = 'API_USER_INACTIVE';
    const WRONG_DESTINATION_GIVEN 		= 'WRONG_DESTINATION_GIVEN';
    const DOMAIN_HAS_FEATURE_DISABLED   = 'DOMAIN_HAS_FEATURE_DISABLED';
    const SKIP_EXTRA_ALIAS              = 'SKIP_EXTRA_ALIAS';
    const SKIP_ADDED_AS_ALIAS_NOT_DOMAIN = 'SKIP_ADDED_AS_ALIAS_NOT_DOMAIN';
    const SKIP_ADDED_AS_DOMAIN_NOT_ALIAS = 'SKIP_ADDED_AS_DOMAIN_NOT_ALIAS';
    const SKIP_PROCESS_OF_ADDON_DOMAINS_DISABLED = 'SKIP_PROCESS_OF_ADDON_DOMAINS_DISABLED';
    const SKIP_OWNER_DOMAIN_NOT_FOUND = "SKIP_OWNER_DOMAIN_NOT_FOUND";
    const SKIP_OWNER_VALIDATION_FAIL = "SKIP_OWNER_VALIDATION_FAIL";


    /**
     * @var SpamFilter_Logger
     */
    protected $_logger;

    /**
    * @var SpamFilter_ResellerAPI
    */
    public $_api;

    /**
    * @var stdClass
    */
    var $_config;

    /**
     * Panel driver instance
     *
     * @access public
     * @var SpamFilter_PanelSupport_Cpanel
     *
     * @todo I'm not 100% sure, but it must be at least protected (or even private)
     */
    public $_panel;

    public function __construct($logger = null, $config = null, $panel = null)
    {
        $this->_logger = null === $logger ? Zend_Registry::get('logger') : $logger;
        if (null !== $config) {
            $this->_config = $config;
        }
        if (null !== $panel) {
            $this->_panel = $panel;
        }

        $this->_api 	= new SpamFilter_ResellerAPI;

        if (!Zend_Registry::isRegistered('general_config')) {
            $this->_logger->debug("[Hooks] Initializing settings.. ");
            Zend_Registry::set('general_config', new SpamFilter_Configuration( CFG_PATH . '/settings.conf' )); // <-- General settings
        }

        $this->_config	= Zend_Registry::get('general_config');
        $this->_panel	= new SpamFilter_PanelSupport();
    }

    /**
     * Adds a domain to the spamfilter
     *
     * @param string $domain Domain to add
     * @param SpamFilter_ResellerAPI $apiRef Override API reference with a local one
     * @param bool $force Force the addition of the domain, would skip remote domains check.
     * @param string $destination Override destination used. If none provided, auto guessing will be done
     * @param array $aliases
     *
     * @return array Statuscode & responses
     * @todo Rewrite this so it has less ifs / is less complex.
     *
     * @access public
     */
    public function AddDomain($domain, SpamFilter_ResellerAPI $apiRef = null, $force = false, $destination = null, $aliases = null)
    {
        $this->_logger->debug("[Hook] Checking if we can add domain '{$domain}'. Auto add domain: {$this->_config->auto_add_domain}, force: {$force}");

        if (!$this->_config->auto_add_domain && !$force) {
            $this->_logger->debug("[Hook] NOT ADDING Domain: '{$domain}' due to setting (auto_add_domain)");
            $response['status'] = false;
            $response['reason'] = "DOMAIN_ADD_DISABLED";
            $response['counts'] = SpamFilter_Panel_Protect::COUNTS_FAILED;

            return $response;
        }

        $this->_logger->debug("[Hook] Checking if domain can be added during feature list permissions '{$domain}', force: {$force}");

        if (!$this->_panel->hasFeatureEnabled($domain)) {
            $this->_logger->debug("[Hook] NOT ADDING Domain: '{$domain}' due to be disabled on featurelist");
            $response['status'] = false;
            $response['reason'] = "DOMAIN_HAS_FEATURE_DISABLED";
            $response['counts'] = SpamFilter_Panel_Protect::COUNTS_FAILED;

            return $response;
        }

        // Check if this domain is remote
        $configAddOnlyLocalDomains = (0 < $this->_config->handle_only_localdomains);
        $isRemoteDomain = $this->_panel->IsRemoteDomain(array('domain' => $domain));

        $logMsg1 = "The 'handle_only_localdomain' option is " . ($configAddOnlyLocalDomains ? 'on' : 'off');
        $logMsg2 = "The domain is detected as " . ($isRemoteDomain ? 'remote' : 'local');
        $this->_logger->debug("[Hook] Evaluating if we can add '{$domain}', since might be a remote domain. {$logMsg1}. {$logMsg2}. Force add is " . ($force ? 'enabled' : 'disabled'));

        if ($configAddOnlyLocalDomains && $isRemoteDomain && !$force) {
            // Not forced, but we handle only the LOCAL domains and this is a remote domain.
            $this->_logger->debug("[Hook] NOT adding domain: '{$domain}', it is a remote domain and this is disabled.");
            $response['status'] = false;
            $response['reason'] = "SKIP_REMOTE";
            $response['counts'] = SpamFilter_Panel_Protect::COUNTS_SKIPPED;
            return $response;
        }

        // Add domain to the AntiSpam appliance
        $this->_logger->debug("[Hook] Adding Domain: '{$domain}'");

        $fallbackRouteIsUsed = false;

        if (empty($destination)) {
            // @TODO: Check this function call as it might return that there is no support even though there is.
            try {
                $this->_logger->debug("[Hook] Attempting to retrieve/use current destination");
                // Obtain the destination
                $destination = $this->_panel->getDestination($domain, 'hook');
            } catch (Exception $e) {
                $this->_logger->debug("[Hook] Unable to use the current destination: No panel support.");
            }

            if ((!isset($destination)) || empty($destination)) {
                $this->_logger->debug("[Hook] No destination override, falling back to this servername");
                $fallbackRouteIsUsed = (0 < $this->_config->use_existing_mx);
                $destination = SpamFilter_Core::GetServerName();
            }
        }

        $asciiSafeDestination = array();
        $idn = new IDNA_Convert;
        foreach (explode(',', $destination) as $eachDestinationHost) {
            $dest = $idn->encode($eachDestinationHost);

            /** @see https://trac.spamexperts.com/ticket/17816 */
            if (0 < $this->_config->use_ip_address_as_destination_routes) {
                $dest = gethostbyname($dest);
            }

            $asciiSafeDestination[] = $dest;
        }

        $addData = array(
            'domain' => $domain,
            'destinations' => Zend_Json::encode($asciiSafeDestination),
        );

        // If we receive aliases we should add them
        if (!empty($aliases)) {
            $addData['aliases'] = Zend_Json::encode($aliases);
        }

        $response = $this->_api->domain()->add($addData);

        if(!is_array($response))
        {
            $this->_logger->err("[Hook-AddDomain] Domain '{$domain}' received a failed response (" . serialize($response) . ")");

            // Before we did not return anything at this point.
            $response['status'] = false;
            $response['reason'] = "SKIP_APIFAIL";
        }
        elseif ( $response['status'] == false && $response['reason'] == "DOMAIN_EXISTS" )
        {
            $this->_logger->err("[Hook-AddDomain] Domain '{$domain}' already exists during addition.");

            // Before we did not return anything at this point.
            $response['status'] = false;
            $response['reason'] = "SKIP_ALREADYEXISTS";
        }
        elseif ( $response['status'] == false && $response['reason'] == "ALREADYEXISTS_NOT_OWNER" )
        {
            $this->_logger->err("[Hook-AddDomain] Domain '{$domain}' already exists and user is not owner.");

            // Before we did not return anything at this point.
            $response['status'] = false;
            $response['reason'] = "ALREADYEXISTS_NOT_OWNER";
        }
        elseif ($response['status'] == false && $response['reason'] == "DOMAIN_LIMIT_REACHED" )
        {
            $this->_logger->err("[Hook-AddDomain] Domain '{$domain}' has NOT been added, limit is reached.");

            $response['status'] = false;
            $response['reason'] = "DOMAIN_LIMIT_REACHED";
        }
        elseif ($response['status'] == false && $response['reason'] == "API_USER_INACTIVE" )
        {
            $this->_logger->err("[Hook-AddDomain] Domain '{$domain}' has NOT been added due to an API error ({$response['reason']}).");

            // Before we did not return anything at this point.
            $response['status'] = false;
            $response['reason'] = "API_USER_INACTIVE";
        }
        elseif ($response['status'] == false && $response['reason'] == "WRONG_DESTINATION_GIVEN" )
        {
            $this->_logger->err("[Hook-AddDomain] Domain '{$domain}' has NOT been added due to an API error ({$response['reason']}).");

            // Before we did not return anything at this point.
            $response['status'] = false;
            $response['reason'] = "WRONG_DESTINATION_GIVEN";
        }
        elseif ($response['status'] == false && $response['reason'] <> "DOMAIN_EXISTS" )
        {
            $this->_logger->err("[Hook-AddDomain] Domain '{$domain}' has NOT been added due to an API error ({$response['reason']}).");

            // Before we did not return anything at this point.
            $response['status'] = false;
            $response['reason'] = "SKIP_APIFAIL";
        } else {

            /**
             * Check do we need to protect the domain's aliases as well
             * @see https://trac.spamexperts.com/ticket/17238
             */

            if (0 < $this->_config->handle_extra_domains &&
                0 < $this->_config->add_extra_alias &&
                is_callable(array($this->_panel, 'getAliasDomains'))
            ) {
                $domainAliases = $this->_panel->getAliasDomains($domain);

                if (!empty($domainAliases) && is_array($domainAliases)) {
                    foreach ($domainAliases as $alias) {
                        $this->_panel->bulkProtect(array(
                            'domain'       => $alias,
                            'type'         => 'alias',
                            'owner_domain' => $domain,
                        ));
                    }
                }
            }

            // Things went fine so lets add the contactdetails to this domain
            if (0 < $this->_config->set_contact) {
                $contact = $this->_panel->getDomainContact(array(
                    'domain' => $domain,
                ));

                if (!empty($contact)) {
                    $this->setContact($domain, $contact);
                } else {
                    $this->_logger->debug(
                        "[Hook] NOT setting contact for '{$domain}' due to missing address in the controlpanel."
                    );
                }
            }

            // Add SPF if setting is enabled
            if ($this->_config->add_spf_to_domains
                && ($response['reason'] == 'DOMAIN_EXISTS' || $response['reason'] == true)) {

                $this->_logger->debug("[Hook] Add/Edit SPF record for: '{$domain}'");
                $spfSetupResult = $this->_panel->SetupSPF(array('domain' => $domain));
                if (!$spfSetupResult) {
                    $this->_logger->debug("[Hook] Add/Edit SPF record failed for: '{$domain}'");
                }
            }
        }

        $result = array('counts' => null, 'reason' => $response['reason'], 'ok' => null, 'status' => $response['status']);
        $domain_failed = $this->isDomainAddFailed( $domain, $response, '[HOOK]' );

        if ($domain_failed['failed']) {
            // Nay!
            $result['counts'] = SpamFilter_Panel_Protect::COUNTS_SKIPPED;
            $result['reason'] = $domain_failed['reason'];
        } else {
            $this->_updateDomainsMxRecords($domain, $apiRef);
        }

        return $result;
    }

    /**
     * Set the contact address for a domain
     *
     * @param string $domain Domainname to set the contact for
     * @param string $contact Contact address to set for the domain
     *
     * @return bool Status
     *
     * @access public
     */
    public function setContact($domain, $contact)
    {
        $this->_logger->info("[Hook] Setting contact for '{$domain}' to '{$contact}'");

        $contactData = array(
                    'domain' => $domain,
                    'email' => $contact,
                );
        $response = $this->_api->domaincontact()->set( $contactData );

        if ( $response['status'] === false ) // new
        {
            $this->_logger->err("[Hook] Setting contact failed!: '" . serialize($response) . "'");
            return false;
        } else {
            $this->_logger->debug("[Hook] Setting contact completed! '" . serialize($response) . "'");
            return true;
        }
    }

    /**
     * Remove a domain from the spamfilter
     *
     * @param string $domain Domain to remove
     * @param bool $force Force removal in case auto-removal is disabled. Only used in special cases
     * @param bool $resetdns
     *
     * @return array Statuscode & responses
     *
     * @access public
     */
    public function DelDomain($domain, $force = false, $resetdns = false)
    {
        if (0 < $this->_config->auto_del_domain || $force) {
            $domainData = array('domain' => $domain);
            if ($resetdns) {
                $this->_logger->info("[Hook] Resetting MX records for '{$domain}'");
                $routes = $this->_api->domain()->getRoute( array('domain' => $domain) );

                SpamFilter_DNS::RevertMXs($domain, $routes);
                $this->_logger->info("[Hook] Removing SPF record for '{$domain}'");
                $this->_panel->RemoveSPF($domain);
            }
            $response = $this->_api->domain()->remove($domainData);

            if ($response['status'] == false) {
                $this->_logger->err(
                    "[Hook] Domain NOT deleted: '{$domain}' due to API error ({$response['reason']})"
                );

                return $response;
            }
            $this->_logger->info("[Hook] Domain deleted: '{$domain}'");

            $response['status'] = true;

            SpamFilter_Panel_Cache::clear('collectiondomains');
            SpamFilter_Core::invalidateDomainsCaches();
            SpamFilter_Panel_Cache::clear('user_domains_' . md5(SpamFilter_Core::getUsername()));

            return $response;
        } else {
            $this->_logger->debug("[Hook] NOT DELETING Domain: '{$domain}' due to setting (auto_del_domain)");
            $response['status'] = false;
            $response['reason'] = "DOMAIN_DEL_DISABLED";

            return $response;
        }
    }

    /**
     * Create an alias in the spamfilter
     *
     * @param string $domain Domain to create the alias for
     * @param string $alias Alternative domainname for $domain
     * @param bool $force Force addition, skips extra domain checks.
     * @param SpamFilter_ResellerAPI $apiRef Optionally override API reference.
     *
     * @return array Status code & response
     *
     * @access public
     */
    public function AddAlias($domain, $alias, $force = false, $apiRef = null)
    {
        $this->_logger->debug("[Hook] Add Alias domain: '{$alias}' and link to '{$domain}'");

        $this->_logger->debug("[Hook] Checking if alias can be added during feature list permissions '{$alias}', domain: '{$domain}', force: {$force}");

        if (!$this->_panel->hasFeatureEnabled($domain)) {
            $this->_logger->debug("[Hook] NOT ADDING Alias: '{$alias}' due to be disabled on featurelist");
            $response['status'] = false;
            $response['reason'] = SpamFilter_Hooks::DOMAIN_HAS_FEATURE_DISABLED;
            $response['counts'] = SpamFilter_Panel_Protect::COUNTS_FAILED;

            return $response;
        }

        // Add domain as alias
        if($this->_config->handle_extra_domains || $force)
        {
            $this->_logger->info("[Hook] Add Alias '{$alias}' to domain '{$domain}'");
            $domainData = array(
                        'domain' => $domain,
                        'alias' => $alias,
                    );
            $response = $this->_api->domainalias()->add( $domainData );

                        SpamFilter_Panel_Cache::clear(strtolower('user_domains_' . md5(SpamFilter_Core::getUsername())));
            if(!is_array($response))
            {
                $this->_logger->err("[Hook-AddAlias] Alias '{$alias}' received a failed response");
            } elseif ($response['status'] == false && in_array($response['reason'], array("DOMAIN_EXISTS", "ALIAS_EXISTS"))) {
                $this->_logger->err("[Hook-AddAlias] Alias '{$alias}' already exists.");

                                return $response;
            }
            elseif ($response['status'] == false )
            {
                $this->_logger->err("[Hook-AddAlias] Alias '{$alias}' has NOT been added due to an API error ({$response['reason']}).");
            }
            else
            {
                // Edit MX to include new records.
                $this->_updateDomainsMxRecords($alias, $apiRef);
            }

            // HMMM
            $response['status'] = true;
            return $response;
        }
        $this->_logger->debug("[Hook] NOT Adding Alias: '{$alias}' to '{$domain}' due to setting (handle_extra_domains)");
        $response['status'] = false;
        $response['reason'] = "ALIAS_DISABLED";
        return $response;
        //return false;

    }

    /**
     * Remove the alias from the spamfilter
     *
     * @param string $domain Domain to remove the alias from
     * @param string $alias Alias to remove
     * @param bool $force Force deletion, skips extra_domain check
     *
     * @return array of status code / responeses
     *
     * @access public
     */
    public function DelAlias($domain, $alias, $force = false)
    {
        $this->_logger->debug("[Hook] Delete Alias domain: '{$alias}' and unlink from '{$domain}'");
        // Delete alias from domain
        if($this->_config->handle_extra_domains || $force)
        {
                        $this->_logger->info("[Hook] Delete Alias '{$alias}' from domain '{$domain}'");
            $domainData = array(
                        'domain' => $domain,
                        'alias' => $alias,
                    );
            $response = $this->_api->domainalias()->remove( $domainData );

            if( $response['status'] == false )
            {
                $this->_logger->err("[Hook] Alias NOT removed: '{$alias}' due to API error ({$response['reason']})");
                return $response;
            }

            // Success
            SpamFilter_Panel_Cache::clear(strtolower('user_domains_' . md5(SpamFilter_Core::getUsername())));
            $this->_logger->debug("[Hook] Alias removed: '{$alias}'");

            $response['status'] = true;
            return $response;
        }
        $this->_logger->debug("[Hook] NOT Deleting Alias: '{$alias}' from '{$domain}' due to setting (handle_extra_domains)");
        $response['status'] = false;
        $response['reason'] = "ALIAS_DISABLED";
        return $response;
        //return false;
    }

    /**
     * Remove a whole account. This requires us to retrieve all domains/aliases and remove them all
     *
     * @param string $username Username that is being removed. Used as filter.
     * @param bool $force Force removal, even if it removals are disabled.
     * @param bool $silent Do not echo results (for internal usage)
     *
     * @return  bool Status code
     *
     * @todo Rewrite this to rely on removal procedures in the PanelSupport driver (e.g. removeAccountDomains( $username ))
     *
     * @access public
     */
    public function DeleteAccount( $username, $force = false, $silent = false )
    {
        // Get all of $username's domains using the panel hook.
        $this->_logger->info("[Hook] DeleteAccount: '{$username}'");
        $result = $this->_panel->getUsersDomains(array(
            'username' => $username
        ));
        //$domains = (!empty($result[0]['domain'])) ? $result['acct'] : null;
        if (count($result) > 0 && is_array($result))
        {
            foreach($result as $domain)
            {
                if (!$silent) echo "Deleting domain: {$domain['domain']}..";
                $this->DelDomain( $domain['domain']/*, $force*/ );
                if (!$silent) echo "Done\n";
            }
        }

        if ( $this->_config->add_extra_alias == 0 )
        {
            // Only handle standalone domains not added as an alias
            // Aliases are already being processed by the SE-api.

            $addonDomains = $this->_panel->getAddonDomains( $username );
            $this->_logger->debug("[WHM] Domain Removal, " . count($addonDomains) . " addon domains: " . serialize($addonDomains) );
            if ($addonDomains !== false)
            {
                foreach ($addonDomains as $key => $addon)
                {
                    $this->_logger->debug("[WHM] Removing addon '{$addon['alias']}'");
                    if (!$silent) echo "Deleting addon domain: {$addon['alias']}..";
                    $this->DelDomain( $addon['alias']/*, $force*/ );
                    if (!$silent) echo "Done\n";
                }
            }

            $parkedDomains = $this->_panel->getParkedDomains( $username );
            $this->_logger->debug("[WHM] Domain Removal, " . count($parkedDomains) . " parked domains: " . serialize($parkedDomains) );
            if ( $parkedDomains !== false )
            {
                foreach ($parkedDomains as $key => $parked)
                {
                    Zend_Registry::get('logger')->debug("[WHM] Removing parked domain '{$parked['alias']}'");
                    if (!$silent) echo "Deleting parked domain: {$parked['alias']}..";
                    $this->DelDomain( $parked['alias']/*, $force*/ );
                    if (!$silent) echo "Done\n";
                }
            }

            $subDomains = $this->_panel->getSubDomains( $username );
            $this->_logger->debug("[WHM] Domain Removal, " . count($subDomains) . " subdomains: " . serialize($subDomains) );
            if ( $subDomains !== false )
            {
                foreach ($subDomains as $key => $subDomain)
                {
                    Zend_Registry::get('logger')->debug("[WHM] Removing subdomain '{$subDomain['alias']}'");
                    if (!$silent) echo "Deleting subdomain: {$subDomain['alias']}..";
                    $this->DelDomain( $subDomain['alias']/*, $force*/ );
                    if (!$silent) echo "Done\n";
                }
            }
        }

        return true;
    }

    /**
     * Update destination / MX records for the domain
     *
     * @param string $domain Domain to update
     * @param string $destination New destination to set. If unset, autoguessing is applied
     * @param SpamFilter_ResellerAPI $apiRef Optionally override API reference
     *
     * @return bool Statuscode
     *
     * @access public
     */
    public function updateData($domain, $destination = null, $apiRef = null)
    {
        $this->_logger->debug("[Hook] Updating data (MX+Destination) for '{$domain}'.");

        if (empty($destination)) {
            // Get our local hostname
            $destination = SpamFilter_Core::GetServerName();
        }

        if (empty($destination)) {
            $this->_logger->err("[Hook] Unable to update data for '{$domain}' since the destination is empty.");

            return false;
        }
        /**
         *  @see https://trac.spamexperts.com/ticket/22936
         */
        if (0 < $this->_config->use_ip_address_as_destination_routes) {
            if (strstr($destination, ',')) {
                foreach (explode(',', $destination) as $dest) {
                    $dest = gethostbyname($dest);
                }
                $newDestination[] = $dest;
            } else {
                $newDestination = array(gethostbyname($destination));
            }
        } else {
            if (strstr($destination, ',')) {
                $this->_logger->err("[Hook] Converting destination list to array.");
                $newDestination = explode(',', $destination);
                $this->_logger->err("[Hook] New destination:" . serialize($destination));
            } else {
                $newDestination = array($destination);
            }
        }

        // Change the destination for the domain
        $data = array(
            'domain' => $domain,
            'destinations' => $newDestination,
        );
        $response = $this->_api->domain()->edit($data);

        if ($response['status'] == false) {
            $this->_logger->err("[Hook] Changing the route for '{$domain}' has failed.");
            return false;
        } else {
            $this->_updateDomainsMxRecords($domain, $apiRef);
            $this->_logger->info("[Hook] The route configuration & MX records for '{$domain}' have been updated.");

            return true;
        }
    }

    /**
     * Domain's MX records setter method
     *
     * @access private
     *
     * @param $domain
     * @param $apiRef
     *
     * @return boolean
     */
    private function _updateDomainsMxRecords($domain, $apiRef)
    { // Update MX records
        if ($this->_config->provision_dns) {
            $this->_logger->debug("[Hook] Changing MX records for: '{$domain}'");
            return SpamFilter_DNS::ConfigureDNS($domain, $apiRef);
        } else {
            $this->_logger->debug(
                "[Hook] NOT changing MX records for: '{$domain}' due to configuration restriction (provision_dns)"
            );
            return false;
        }
    }

    /**
     * Toggle the operation of the spamfilter based on the MXtype
     *
     * @param string $domain Domainname to switch for
     * @param string $mxtype Type to work on
     *
     * @return mixed
     *
     * @access public
     */
    public function setMailHandling($domain, $mxtype)
    {
        $this->_logger->info("[Hook] {$domain}'s route has changed to '{$mxtype}'");

        if (!$this->_config->handle_route_switching) {
            $this->_logger->info("[Hook] NOT executing actions linked to routing change, disabled in settings.");

            return false;
        }

        $this->_logger->info("[Hook] Executing actions linked to routing change.");

        // @TODO: Implement changing status if the status is set to 'local'.
        // Auto / remote / backup does not count and should not be acted upon.
        if (strtolower($mxtype) <> "local") {
            // Domain NOT set to local, we should stop handling it
            if ($this->_config->provision_dns) {
                $this->_logger->info("[Hook] {$domain}'s MX records will be automatically reset, as configured in settings.");

                /**
                 * Force remove the domain but skip MX records reset
                 *
                 * @see https://github.com/SpamExperts/cpanel-addon/issues/10
                 */
                $this->DelDomain($domain, true, false);

                return $this->safeResetDns($domain);
            } else {
                $this->_logger->info("[Hook] {$domain}'s MX records will NOT be automatically reset, as configured in settings.");

                return $this->DelDomain($domain, true); // Force remove it and don't change MX records
            }
        } else {
            // Domain set to local, we should handle it.
            $this->_logger->debug("[Hook] Domain '{$domain}' has been changed to local, making sure it exists in the filtering solution.");

            return $this->AddDomain( $domain );
        }
    }

    //
    public function psa_CheckMxRecords( $domain )
    {
        $this->_logger->info("[Hook] Checking {$domain}'s records (for Plesk)");
        return $this->_panel->removeForeignMXRecords( $domain );
/*

        // Retrieve MX records and compare with the templated ones.
        $mxr = $this->_panel->GetMXRecordContent( $domain );
        if( (!empty($mxr)) && ($mxr !== false) )
        {
            $my_rr[] = $this->_config->mx1;
            if(!empty($this->_config->mx2)){ $my_rr[] = $this->_config->mx2; }
            if(!empty($this->_config->mx3)){ $my_rr[] = $this->_config->mx3; }
            $myRRCount = count($my_rr);
            $foundRR = 0;

            // Check if they aren't already pointing to the filter cluster.
            foreach ($mxr as $r)
            {
                // $r = record
                if(in_array($r, $my_rr))
                {
                    $foundRR++;
                    // Record $r exists in array $my_rr
                }
            }

            $c1 = count($mxr);
            if( $c1 > $myRRCount )
            {
                $this->_logger->debug("More records found ({$c1}) than required ({$myRRCount}). Re-running DNS setup");
                return SpamFilter_DNS::ConfigureDNS($domain);
            }

            // Second check
            $diff = array_diff($my_rr, $mxr);
            if( (count($diff) > 0) && (!in_array($my_rr, $diff)) )
            {
                $this->_logger->debug("Second level check required. Difference: " . serialize($diff) );
            }
        }
*/
    }
    //

    /**
     * Check a domain added or not
     *
     * @param string $domain domain
     * @param array  $params array Statuscode & responses
     * @param string $debugStr
     *
     * @return  array failed & reason - if a domain hasn't been added 'failed' = true
     *
     * @access public
     */
    public function isDomainAddFailed($domain, $params, $debugStr)
    {
        $result = array('failed' => false, 'reason' => '');
        if (!is_array($params)) {
            $result['failed'] = true;
            $result['reason'] = self::SKIP_APIFAIL;
            $this->_logger->debug(
                $debugStr . " Normal domain '" . $domain . "' has NOT been added because of an API failure."
            );
        } elseif ($params['reason'] == self::DOMAIN_LIMIT_REACHED) {
            $result['failed'] = true;
            $result['reason'] = self::DOMAIN_LIMIT_REACHED;
            $this->_logger->debug(
                $debugStr . " Normal domain '" . $domain . "' has NOT been added because domain limit is reached."
            );
        } elseif ($params['reason'] == self::API_REQUEST_FAILED) {
            $result['failed'] = true;
            $result['reason'] = self::SKIP_APIFAIL;
            $this->_logger->debug(
                $debugStr . " Normal domain '" . $domain . "' has NOT been added because of an API error."
            );
        } elseif ($params['reason'] == self::API_USER_INACTIVE) {
            $result['failed'] = true;
            $result['reason'] = self::API_USER_INACTIVE;
            $this->_logger->debug(
                $debugStr . " Normal domain '" . $domain . "' has NOT been added because of the API user is inactive."
            );
        } elseif ($params['reason'] == self::WRONG_DESTINATION_GIVEN) {
            $result['failed'] = true;
            $result['reason'] = self::WRONG_DESTINATION_GIVEN;
            $this->_logger->debug(
                $debugStr . " Normal domain '" . $domain . "' has NOT been added because of a wrong destination has been given."
            );
        } elseif ($params['status'] == false
            && !in_array(
                $params['reason'], array(self::DOMAIN_EXISTS, self::SKIP_ALREADYEXISTS)
            )
        ) {
            $result['failed'] = true;
            $result['reason'] = self::SKIP_UNKNOWN;
            $this->_logger->debug(
                $debugStr . " Normal domain '" . $domain . "' has NOT been added ({$params['reason']})."
            );
        }
        return $result;
    }

    /**
     * isDataUpdated
     * Check data updated or not
     *
     * @param string $status status
     * @param $loginfo array of string (updated,skipped) info for logger
     *
     * @return  array action & reason
     *
     * @access public
     */
    public function isDataUpdated( $status, $loginfo )
    {
        if ($status) {
            // OK
            $result['action'] = 'updated';
            $result['reason'] = self::ALREADYEXISTS_ROUTESET;
            $this->_logger->debug($loginfo['updated']);
        } else {
            // Failed
            $result['action'] = 'skipped';
            $result['reason'] = self::ALREADYEXISTS_ROUTESETFAIL;
            $this->_logger->debug($loginfo['skipped']);
        }
        return $result;
    }

    /**
     * Migrate the domain to a different user
     *
     * @param string $domain 	Domain to move
     * @param string $user 	User to move the domain to
     * @param string $password Password of the destination user
     *
     * @return bool Status
     *
     * @access public
     */
    public function MigrateDomain( $domain, $user, $password )
    {
        // Change the owner for the domain
        $data = array(
                    'domains' 				=> $domain,
                    'destination_username' 	=> $user,
                    'destination_password' 	=> $password,
                );
        $response = $this->_api->domain()->move( $data );

        if ( $response['status'] == false )
        {
            if(is_array($domain))
            {
                $c = count($domain);
                $this->_logger->debug("[Hook] {$c} domains cannot be migrated to user '{$user}'");
            } else {
                $this->_logger->err("[Hook] Changing the owner for '{$domain}' has failed.");
            }
            return false;
        } else {
            if(is_array($domain))
            {
                $c = count($domain);
                $this->_logger->debug("[Hook] {$c} domains have succesfully been migrated to user '{$user}'");
            } else {
                $this->_logger->debug("[Hook] Domain '{$domain}' has succesfully been migrated to user '{$user}'");
            }
            return $response;
        }
    }

    /**
     * Get free limit for migration domains
     *
     * @param string $user 	User to move the domain to
     * @param string $password Password of the destination user
     *
     * @return integer|string|bool Free limit
     *
     * @access public
     */
    public function GetFreeLimit( $user, $password )
    {
        $data = array(
                    'destination_username' 	=> $user,
                    'destination_password' 	=> $password,
                );
        $response = $this->_api->domain()->getfreelimit( $data );

        if ( empty($response['result']['freelimit']))
        {
            $this->_logger->debug("[Hook] cannot get free limit");

            return false;
        } else {
            $this->_logger->debug("[Hook] free limit is '".$response['result']['freelimit']."'");
            return $response['result']['freelimit'];
        }
    }

    /**
     * Get the domain destination(s)
     *
     *  @param string $domain 	Domain
     *
     * @return array of string Destinations
     *
     * @access public
     */
    public function GetRoute( $domain )
    {
        $routes = SpamFilter_Panel_Cache::get(md5($domain) . '_routes');
        $info = array('additional' => '', 'reason' => '');
        if (empty($routes)) {
            $routes = array();
            $response = (array)$this->_api->domain()->getroute( array('domain' => $domain) );
            if (!empty($response) && empty($response["reason"])) {
                try {
                    $this->_logger->debug("[Hook] routes '".implode(',',(array)$response)."'");
                    foreach ($response as $item) {
                        $routes[] = explode(':', $item);
                    }
                 }catch( Exception $e ) {}
             } else {
                $info = array('additional' => $response["additional"], 'reason' => $response["reason"]);
             }
             SpamFilter_Panel_Cache::set(md5($domain) . '_routes', $routes, 10);
        }

        return array('routes' => $routes, 'info' => $info);
    }

    /**
     * This method implements the procedure of "safe" MX records reset as
     * https://github.com/SpamExperts/cpanel-addon/issues/10 suggests
     *
     * @access public
     *
     * @param string $domain
     *
     * @return boolean
     */
    public function safeResetDns($domain)
    {
        $existimgMxRecords = $this->_panel->getMxRecords($domain);

        if (is_array($existimgMxRecords)) {
            $recordsRemoved = 0;
            $spamfilterMxRecords = $this->getFilteringClusterHostnames();

            // Sort existing MX records by line in DESC order to avoid attempting
            // entries with obsolete line reference
            usort($existimgMxRecords, function ($a, $b) { return $b['Line'] > $a['Line'] ? 1 : -1; });
            foreach ($existimgMxRecords as $existimgMxRec) {
                if (in_array($existimgMxRec['exchange'], $spamfilterMxRecords)) {
                    $this->_panel->removeDNSRecord($domain, $existimgMxRec['Line']);
                    $recordsRemoved++;
                }
            }

            if ($recordsRemoved == count($existimgMxRecords)) {
                $this->_panel->addMxRecord($domain, 10, $this->getFallbackMxRecordHostname());
            }
        }

        return true;
    }

    /**
     * Wrapper for \SpamFilter_DNS::getFilteringClusterHostnames() for testability
     *
     * @return array
     */
    public function getFilteringClusterHostnames()
    {
        return \SpamFilter_DNS::getFilteringClusterHostnames();
    }

    /**
     * Wrapper for \SpamFilter_Core::GetServerName() for testability
     *
     * @return string
     */
    public function getFallbackMxRecordHostname()
    {
        return gethostname();
    }
}
