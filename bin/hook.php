#!/usr/local/cpanel/3rdparty/bin/php
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

require_once dirname(__FILE__) . '/../application/bootstrap.php';

$paneltype = SpamFilter_Core::getPanelType();
$domain = $email = $newdomain = $user = '';
$domains = array();
$hook = new SpamFilter_Hooks;

if (!Zend_Registry::isRegistered('general_config')) {
    // Initialize the config if this is not set.
    $configuration = new SpamFilter_Configuration(CFG_FILE);
}
$config = Zend_Registry::get('general_config');
$protectionManager = new SpamFilter_ProtectionManager();

$in = file_get_contents("php://stdin");
if (!empty($in)) { // Cpanel: STDIN used
    $_panel = new SpamFilter_PanelSupport_Cpanel;
    Zend_Registry::get('logger')->debug("[Hook] STDIN received");
    Zend_Registry::get('logger')->debug("[Hook] STDIN:\n{$in}\n");

    // Now it is JSON
    $dataArray = json_decode($in, true);
    $action = translateCPHookNames($dataArray['context']['event'],$dataArray['context']['stage']);

    switch($action){
        case 'predelaccount':
            $user = $dataArray["data"]["user"];
            break;
        case 'adddomain':
                                $domain = $dataArray['data']['domain'];
                                $mxtype = $dataArray['data']['mxcheck'];
                                break;
        case 'deldomain':
            $username = $dataArray['data']['user'];
            $domains[] = $_panel->getMainDomain($username);
            $addonDomains = $_panel->getAddonDomains($username);

            if ($addonDomains) {
                $addonDomains = array_map('getAliasFromArray', $addonDomains);
                $domains = array_merge($domains, $addonDomains);
            }

            $parkedDomains = $_panel->getParkedDomains($username);

            if ($parkedDomains) {
                $parkedDomains = array_map('getAliasFromArray', $parkedDomains);
                $domains = array_merge($domains, $parkedDomains);
            }

            break;
        case 'addaddondomain':
                                $domain = $_panel->getMainDomain($dataArray['data']['user']);
                                $alias = $dataArray['data']['args']['newdomain'];
                                break;
        case 'addsubdomain':
                                $domain = $_panel->getMainDomain($dataArray['data']['user']);
                                $alias = $dataArray['data']['args']['domain'].".".$domain;
                                break;
        case 'delsubdomain':
        case 'deladdondomain':
                                $domain = $_panel->getMainDomain($dataArray['data']['user']);
                                $alias = $dataArray['data']['args']['domain'];
                                $alias = str_replace('_', '.', $alias);
                                break;
        case 'park':
                                $domain = $dataArray['data']['target_domain'];
                                $alias = $dataArray['data']['new_domain'];
                                break;

        case 'unpark':
                                $domain = $dataArray['data']['parent_domain'];
                                $alias = $dataArray['data']['domain'];

                                break;
        case 'savecontactinfo':
                                $email = $dataArray['data']['args']['email'];
                                $domain = $_panel->getMainDomain($dataArray['data']['user']);
                                break;
        case 'modifyaccount':
                                $email = $dataArray['data']['contactemail'];
                                $domain = $dataArray['data']['domain'];
                                $action = 'savecontactinfo';
                                break;
        case 'setmxcheck':      $mxtype = $dataArray['data']['args']['mxcheck'];
                                $domain = $dataArray['data']['args']['domain'];
                                break;
        case 'restore':
                                $user = $dataArray['data']['user'];
                                $domain = $_panel->getMainDomain($user);
                                break;
        default:                die('Wrong action provided! Aborting!');
    }
} else {
    die("No output from API!");
}

    // strip www. part from the name
if (!empty($domain)) {
    $domain = preg_replace('/^www\./i', '', $domain);
}
if (!empty($alias)) {
$alias = preg_replace('/^www\./i', '', $alias);
}

//
// EXECUTE
//
$response = '';
switch( $action )
{
	case "adddomain":
        if (isset($domain)) {

            /**
             * Check for real domain's MX type in case of it's not 'local'
             *
             * @see https://trac.spamexperts.com/software/ticket/15861
             */
            if (isset($mxtype) && $mxtype <> 'local') {
                /** @var $panelDriver SpamFilter_PanelSupport_Cpanel */
                $panelDriver = new SpamFilter_PanelSupport;
                if (!$panelDriver->IsRemoteDomain(array('domain' => $domain))) {
                    $mxtype = 'local';
                }
            }

            // Creation of domain
            if ((isset($mxtype) && ($mxtype <> "local")) && ($config->handle_only_localdomains)) {
                $response .= "\nNOT Adding '{$domain}' to the Antispam filter because the Mail Routing Settings have been set to '{$mxtype}' and remotedomain skipping is enabled";
            } else {
                if ($config->auto_add_domain) {
                    $response .= "\nAdding '{$domain}' to the Antispam filter...";
                    $status = $protectionManager->protect($domain, null, "domain");

                    if (!empty($status['reason'])) {
                        $response .= " {$status['reason']} ";
                    }
                } else {
                    $response .= "\nNOT Adding '{$domain}' to the Antispam filter, because adding domains is disabled in the settings.";
                }
            }
        }

        break;

	case "deldomain":
		// Deletion of domain
		if( $config->auto_del_domain )
		{
            $response .= "\n Preparing to delete from the Antispam filter";

            foreach ($domains as $domain) {
                $response .= "\nDeleting '{$domain}' from the Antispam filter...";
                $hook->DelDomain( $domain );
            }

            $status = array('status' => true);
		} else {
			$response .= "\nNOT deleting '{$domain}' from the Antispam filter, because removing domains is disabled in the settings.";
		}
		break;

    case "park":
	case "addsubdomain":
    case "addaddondomain":
        if (empty($alias)) {
            Zend_Registry::get('logger')->debug("[Hook] Alias not supplied. Cannot proceed");
            return false;
        }

        if (!$config->handle_extra_domains) {
            return false;
        }

        if (!$config->auto_add_domain) {
            return false;
        }

        $type = getSecondaryDomainType($action);
        $response .= "\nAdding secondary domain '{$alias}' to the Antispam filter...";
        $status = $protectionManager->protect($alias, $domain, $type);

        break;

    case "restore":
        Zend_Registry::get('logger')->debug("[Hook] Restoring addon and parked domains");

        if (!$config->handle_extra_domains) {
            return false;
        }

        if (!$config->auto_add_domain) {
            return false;
        }

        if (!empty($user) && !empty($domain)) {
            /** @var $panel SpamFilter_PanelSupport_Cpanel */
            $panel = new SpamFilter_PanelSupport();
            $addonDomains = $panel->getAddonDomains($user, $domain);

            foreach ($addonDomains as $addonDomain) {
                if ($config->add_extra_alias) {
                    // Add as alias
                    $response .= "\nAdding '{$addonDomain['alias']}' as alias of '{$domain}' to the Antispam filter...";
                    $status = $hook->AddAlias($domain, $addonDomain['alias']);
                } else {
                    // Add as normal domain.
                    $response .= "\nAdding '{$addonDomain['alias']}' to the Antispam filter...";
                    $status = $hook->AddDomain($addonDomain['alias']);
                }
            }

            $parkedDomains = $panel->getParkedDomains($user);

            foreach ($parkedDomains as $parkedDomain) {
                if ($config->add_extra_alias) {
                    // Add as alias
                    $response .= "\nAdding '{$parkedDomain['alias']}' as alias of '{$domain}' to the Antispam filter...";
                    $status = $hook->AddAlias($domain, $parkedDomain['alias']);
                } else {
                    // Add as normal domain.
                    $response .= "\nAdding '{$parkedDomain['alias']}' to the Antispam filter...";
                    $status = $hook->AddDomain($parkedDomain['alias']);
                }
            }
        } else {
            Zend_Registry::get('logger')->debug("[Hook] Empty user or domain supplied so there's nothing to restore");
            return false;
        }

        break;

	case "unpark":
    case "delsubdomain":
	case "deladdondomain":
		if(empty($alias))
		{
			Zend_Registry::get('logger')->debug("[Hook] Alias not supplied. Cannot proceed");
			return false;
		}
		if(!$config->handle_extra_domains) { return false; }// Extra/Addon domains DISABLED in plugin
		if(!$config->auto_del_domain ) { return false; } // The admin said he did not want to have domains removed from the filter.

        $type = getSecondaryDomainType($action);
        $response .= "\nDeleting '{$alias}' (alias from '{$domain}') from the Antispam filter...";
        $status = $protectionManager->unprotect($alias, $domain, "alias");
		break;

	case "predelaccount":
		// account ($data['user'] will be removed)
		// We need to get all of the domains associated to this acct.
		$response .= "\nDeleting all domains of '{$user}' from the Antispam filter...\n";
        $status = $hook->DeleteAccount($user);
		break;

	case "delaccount":
		$response .= "\nDelete account";
		// account ($data['user'] will be removed)
		// We need to get all of the domains associated to this acct.
		break;

	case "editdomain":
		$response .= "\nEdit domain (DIRECTADMIN)";

		$response .= " Deleting '{$domain}' from the Antispam filter...";
		$status = $hook->DelDomain( $domain );

		$response .= " Adding '{$domain}' to the Antispam filter...";
		$status = $hook->AddDomain( $newdomain );
		break;

	case "savecontactinfo":
		// Change email address for the user. (cpanel)
		$status = $hook->setContact($domain, $email);
		if( $status )
		{
			$response .= "\nYour Antispam email address for domain '{$domain}' has been set to '{$email}'.";
		} else {
			$response .= "\nCould not set your antispam email address for domain '{$domain}'.";
		}

		break;

	case "setmxcheck":
		if( isset($mxtype) && (!empty($mxtype)) )
		{
            $status = $hook->setMailHandling($domain, $mxtype);
		} else {
			Zend_Registry::get('logger')->err("[Hook] Unable to set mail handling with missing mxtype.");
		}
	break;

	case "postdomainadd":
		// Re-check the MX records and remove the ones that don't belong.
		// Currently only needed in Plesk
		Zend_Registry::get('logger')->info("[Hook] Doing postdomainadd for Plesk (Domain: {$domain}).");
        $status = $hook->AddDomain($domain);
	break;

	default:
		$response .= "\nUnknown option";
		return false;
		break;
}

if (isset($status['status'])) {
    if (empty($status['status'])) {
        $response .= " Failed!\n";
    } else {
        $response = "1 " . $response;
    }

    echo $response;
}

function translateCPHookNames($event, $stage){
    if($stage == 'pre'){
    $translate = array( 'Accounts::Remove'                     =>  'predelaccount',
                        'Api2::SubDomain::delsubdomain'        =>  'delsubdomain',
                        'Api2::AddonDomain::deladdondomain'    =>  'deladdondomain',
                        'Domain::unpark'                       =>  'unpark',
                 );
    } else {
    $translate = array( 'Accounts::Create'                     =>  'adddomain',
                        'Accounts::Modify'                     =>  'modifyaccount',                        
                        'Restore'                              =>  'restore',
                        'Domain::park'                         =>  'park',
                        'Api2::SubDomain::addsubdomain'        =>  'addsubdomain',
                        'Api2::AddonDomain::addaddondomain'    =>  'addaddondomain',
                        'Api2::CustInfo::savecontactinfo'      =>  'savecontactinfo',
                        'Api2::Email::setmxcheck'              =>  'setmxcheck');
    }
    return $translate[$event];
}

function getAliasFromArray($data) {
    return $data['alias'];
}

function getSecondaryDomainType($action) {
    if (in_array($action, array("addsubdomain", "delsubdomain"))) {
        return "subdomain";
    }

    if (in_array($action, array("unpark", "park"))) {
        return "parked";
    }

    if (in_array($action, array("addaddondomain", "deladdondomain"))) {
        return "addon";
    }

    Zend_Registry::get('logger')->info("[Hook] Unknown secondary domain type for $action.");

    return null;    
}