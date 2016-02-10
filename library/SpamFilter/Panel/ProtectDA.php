<?php
/**
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
* @category  SpamExperts
* @package   ProSpamFilter
* @author    $Author$
* @copyright Copyright (c) 2011, SpamExperts B.V., All rights Reserved. (http://www.spamexperts.com)
* @license   Closed Source
* @version   3.0
* @link      https://my.spamexperts.com/kb/34/Addons
* @since     2.5
*/

class SpamFilter_Panel_ProtectDA extends SpamFilter_Panel_Protect
{

    /**
     * Protect domain container
     *
     * @access protected
     * @var string
     */
    protected $_domain;



    /**
     * Domain setter
     *
     * @access public
     * @param string $domain
     * @return void
     */
    public function setDomain($domain)
    {
       $this->_domain = $domain;
       $this->_result['domain'] = $domain;
    }

    /**
     * Domain protected method
     *
     * @param string Value of destination host
     * 
     * @access public
     * @return array 
     */
    public function domainProtectHandler ($destination)
	{
	    $this->_logger->debug("[DA] Handling domain: '" . $this->_domain . "'");
		// Validate domainname
		if (SpamFilter_Core::validateDomain( $this->_domain ) == false)
		{
			$this->_logger->debug("[DA] Filtering invalid domain value: '{$this->_domain}'");

			// Hmm, domain is invalid. Shouldn't be happening. Let's skip this one then.
			$this->countsUp(SpamFilter_Panel_Protect::COUNTS_SKIPPED);
			$this->addDomainReason($this->_domain, SpamFilter_Hooks::SKIP_INVALID);
			return;
		}
		// @TODO: We should probably only handle domains that use LOCAL MAIL facilities.
		$this->_logger->debug("[DA] Requesting addition of normal domain: '" . $this->_domain . "'");
        $result = $this->_hook->AddDomain($this->_domain, null, true, $destination);
       
        $this->countsUp($result['counts']);
		if ($result['ok'] == SpamFilter_Panel_Protect::COUNTS_OK) {
			$this->countsUp(SpamFilter_Panel_Protect::COUNTS_OK);
	    }
		$this->addDomainReason($this->_domain, $result['reason']);
	}
	
	/**
     * Alias domain protected method
     *
     * @param string alias domain params
     * @param string Value of destination host
     * 
     * @access public
     * @return void
     */
	public function aliasDomainProtectHandler ($alias, $destination)
	{
        if ( SpamFilter_Core::validateDomain($alias) == false)
		{
			$this->_logger->debug("[DA] Filtering invalid domain value '{$alias}' for alias domain");

			// Hmm, domain is invalid. Shouldn't be happening. Let's skip this one then.
			$this->countsUp(self::COUNTS_SKIPPED);
			$this->addDomainReason($alias, SpamFilter_Hooks::SKIP_INVALID);
		    return;
		}

		// Check add type
		if( $this->_config->add_extra_alias )
		{
			// Add as alias
			$this->_logger->debug("[DA] Adding addon domain '{$alias}' as an alias of '" . $this->_domain . "'.");
			$return = $this->_hook->AddAlias($this->_domain, $alias, true, null);
			$keyvalue = "OK_ALIAS";
		} else {
			// Add as REAL domain
			$this->_logger->debug("[DA] Adding alias domain '{$alias}' as a real domain.");

			$return = $this->_hook->AddDomain($alias, null, true, $destination);
			$keyvalue = "OK";
		}

		//
		if ( (isset($return)) && (!is_array($return)) )
		{
			$this->countsUp(self::COUNTS_FAILED);
			$this->_logger->debug("[DA] Alias domain '{$alias}' produced wrong return values in the process.");
			$this->addDomainReason($alias, 'FAIL');
		}
		elseif ( $return['status'] == false && ($return['reason'] == SpamFilter_Hooks::ALIAS_EXISTS || $return['reason'] == SpamFilter_Hooks::DOMAIN_EXISTS || $return['reason'] == SpamFilter_Hooks::SKIP_ALREADYEXISTS) )
		{
			if( $this->_config->provision_dns && $this->_config->bulk_force_change && ($return['reason'] == SpamFilter_Hooks::DOMAIN_EXISTS ) ) // Only do this when it has been completed succesfully, it is a DOMAIN not an alias
			{
				// Domain already exists, but update the route + mx records please.
				$status = $this->_hook->updateData( $alias, $destination);
				$data_updated = $this->_hook->isDataUpdated($status, array ('updated' => "[DA-BULK] Alias domain '{$alias}' already exists but route & MX has been updated.",
								                                            'skipped' => "[DA-BULK] Alias domain '{$alias}' already exists but route/mx change has failed."));
				$this->countsUp($data_updated['action']);
				$this->addDomainReason($alias, $data_updated['reason']);
		
			} else {
				// Domain already exists, this is just a notice though.
				$this->countsUp(self::COUNTS_SKIPPED);
				$this->addDomainReason($alias, SpamFilter_Hooks::SKIP_ALREADYEXISTS);

				$this->_logger->debug("[DA-BULK] Alias domain '{$alias}' has been skipped because it was added already.");
			}
		}
		elseif ($return['status'] == false )
		{
			$this->countsUp(self::COUNTS_FAILED);
			$this->_logger->debug("[DA] Alias domain '{$alias}' has NOT been added due to an API error ({$return['reason']}).");
			$this->addDomainReason($alias, SpamFilter_Hooks::SKIP_APIFAIL);
		} else {
			// Addon added!.
			$this->countsUp(self::COUNTS_OK);
			$this->countsUp(self::COUNTS_PARKED);
			$this->_logger->debug("[DA] Alias domain '{$alias}' has been added.");
			$this->addDomainReason($alias, $keyvalue);
		}
	}

}
