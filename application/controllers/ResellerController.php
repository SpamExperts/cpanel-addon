<?php
/*
*************************************************************************
*                                                                       *
* ProSpamFilter3                                                        *
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
/** Zend_Controller_Action */
class ResellerController extends Zend_Controller_Action
{
    /** @var SpamFilter_Acl */
	protected $_acl;

    /** @var SpamFilter_Controller_Action_Helper_FlashMessenger */
    var $_flashMessenger;

    /** @var Zend_Translate_Adapter_Gettext */
    var $t;

    /**
     * Specific panel manager instance
     *
     * @access protected
     * @var SpamFilter_PanelSupport_Cpanel
     */
	protected $_panel;

	public function init()
	{
		try {
		    // Enable the flash messenger helper so we can send messages.
		    $this->_flashMessenger = $this->_helper->getHelper('FlashMessenger');
        } catch (Zend_Session_Exception $e) {
		    if (!$this->_helper->hasHelper('FlashMessenger')) {
				if (!Zend_Session::isStarted() && Zend_Session::sessionExists()) {
				    Zend_Controller_Action_HelperBroker::addHelper(
                        new SpamFilter_Controller_Action_Helper_FlashMessenger()
                    );
                    $this->_flashMessenger = Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger');
				} else {
					Zend_Session::setOptions(array ("strict" => false));
				    $this->_flashMessenger = $this->_helper->getHelper('FlashMessenger');
				    Zend_Session::setOptions(array ("strict" => true));
			    }
			}
        }
	}

	public function preDispatch()
	{
		// Setup ACL
		$this->_acl = new SpamFilter_Acl();

		$username = SpamFilter_Core::getUsername();

		// Retrieve usertype using the Panel driver
		$this->_panel = new SpamFilter_PanelSupport( );
		$userlevel = $this->_panel->getUserLevel();

		// Feed the ACL system the current username
		$this->_acl->setRole( $username, $userlevel );

        // Get the translator
        $this->t = Zend_Registry::get('translator');

        /**
         * Get changed brandname (in case of it was set)
         * @see https://trac.spamexperts.com/ticket/16804
         */
        $branding   = new SpamFilter_Brand();
        $brandname  = $branding->getBrandUsed();
        if (!$brandname) {
            $brandname = 'Professional Spam Filter';
        }

		$this->view->headTitle( $brandname);
		$this->view->headTitle()->setSeparator(' | ');
		$this->view->headStyle()->appendStyle( file_get_contents(BASE_PATH . DS . 'public' . DS . 'css' . DS . 'bootstrap.min.css') );
		$this->view->headStyle()->appendStyle( file_get_contents(BASE_PATH . DS . 'public' . DS . 'css' . DS . 'bootstrap-responsive.min.css') );
		$this->view->headStyle()->appendStyle( file_get_contents(BASE_PATH . DS . 'public' . DS . 'css' . DS . 'addon.css') );
		$this->view->headScript()->appendScript( file_get_contents(BASE_PATH . DS . 'public' . DS . 'js' . DS . 'jquery.min.js') );
		$this->view->headScript()->appendScript( file_get_contents(BASE_PATH . DS . 'public' . DS . 'js' . DS . 'bootstrap.min.js') );

		$this->view->acl = $this->_acl;
        $this->view->t = $this->t;
        $this->view->brandname = $brandname;
		$this->view->hasAPIAccess = $branding->hasAPIAccess();
	}

	public function listdomainsAction()
	{
                $this->view->headTitle()->append("List domains");
		if( !$this->_acl->isAllowed('list_domains') )
		{
			$this->_flashMessenger->addMessage( array('message' => $this->t->_('You do not have permission to this part of the system.'), 'status' => 'error') );
			$this->_helper->viewRenderer->setNoRender(); // Do not render the page
			return false;
		}

		$config = Zend_Registry::get('general_config');
		$this->view->isConfigured = (!empty($config->apiuser)) ? true : false;
		if(!$this->view->isConfigured) { return false; }

                // Items Per Page functionality
                $itemsPerPage = $this->_getParam('items')? (int)htmlspecialchars($this->_getParam('items')) : 25 ;
                if($itemsPerPage<1 || $itemsPerPage > 25){
                    $this->view->itemsPerPageLimit = $this->t->_('The items per page parameter should be an integer greater than or equal to 1 and less than or equal to 25');
                    $itemsPerPage = 25;
                }

                // Get params
                $filter = htmlspecialchars($this->_getParam('search'));
                $order = $this->_getParam('sortorder');
		$oldorder = SpamFilter_Panel_Cache::get( 'domains_sort_order' );

                // Get domain from cache if not root
                if(SpamFilter_Core::getUsername() != 'root'){
                    $domains = SpamFilter_Panel_Cache::get(SpamFilter_Core::getDomainsCacheId());
                }

		if (!empty($order) && in_array($order, array('asc', 'desc'))) {
                    SpamFilter_Panel_Cache::set( 'domains_sort_order',  $order);
		}

		$order = SpamFilter_Panel_Cache::get( 'domains_sort_order' );

        if ($order != $oldorder) {
            $domains = $this->_panel->getSortedDomains(array ('domains' => $domains, 'order' => $order));
            SpamFilter_Panel_Cache::set(SpamFilter_Core::getDomainsCacheId(), $domains);
		}

		// No cache set, proceed with retrieval
		if (empty($domains)) {
		    $domains = $this->_panel->getDomains(
                array(
                    'username' => SpamFilter_Core::getUsername(),
                    'level'    => 'owner',
                    'order'    => $order,
                )
            );

            // Cache miss, save the data
			SpamFilter_Panel_Cache::set(SpamFilter_Core::getDomainsCacheId(), $domains);
		}

		// Proceed
		if ( !isset($domains))
		{
			$this->_flashMessenger->addMessage(
							array(
								'message' => $this->t->_('Unable to retrieve domains.'),
								'status' => 'error'
								)
							);
			return false;
		}

		if ((empty($domains)) || (is_countable($domains) && count($domains) === 0) )
		{
			$this->_flashMessenger->addMessage(
							array(
								'message' => $this->t->_('There are no domains on this server.'),
								'status' => 'info'
								)
							);
			return false;
		}

                if(!empty($filter)){
                    $domains = $this->_panel->filterDomains(array('domains' => $domains, 'filter' => $filter));
                }
                if ((empty($domains)) || (is_countable($domains) && count($domains) == 0) )
		{
			$this->_flashMessenger->addMessage(
							array(
								'message' => $this->t->_('There are no domains that meet your requirements.'),
								'status' => 'info'
								)
							);
                        $domains = array();
		}
		$paginator = new Zend_Paginator(new Zend_Paginator_Adapter_Array($domains));
		$paginator->setItemCountPerPage($itemsPerPage)
				  ->setCurrentPageNumber($this->_getParam('page', 1));
                $totalItems = $paginator->getTotalItemCount();
                $currentPage = $paginator->getCurrentPageNumber();
                $pageCount = $paginator->count();
                $pagesInfo = 'Page ' . $currentPage . ' of ' . $pageCount . '. Total Items: ' . $totalItems . '. Items per page: ' . $itemsPerPage. '. ';

		$this->view->paginator = $paginator;
                $this->view->searchValue = $filter;
		$this->view->sortorder = $order;
                $this->view->pagesInfo = $pagesInfo;
                $this->view->items = $itemsPerPage;
                $this->view->accesslevel = strtolower($this->_panel->getUserLevel());
	}

	public function listaccountsAction()
	{
		$this->view->headTitle()->append("List accounts");
		if( !$this->_acl->isAllowed('list_accounts') )
		{
			$this->_flashMessenger->addMessage( array('message' => $this->t->_('You do not have permission to this part of the system.'), 'status' => 'error') );
			$this->_helper->viewRenderer->setNoRender(); // Do not render the page
			return false;
		}

		$config = Zend_Registry::get('general_config');
		$this->view->isConfigured = (!empty($config->apiuser)) ? true : false;
		if(!$this->view->isConfigured) { return false; }

		$cacheKey = strtolower( 'reseller_accounts' );
		$accounts = SpamFilter_Panel_Cache::get( $cacheKey );

		// No cache set, proceed with retrieval
		if( !$accounts )
		{
			$accounts = $this->_panel->getPrimaryUsers( array('username' => SpamFilter_Core::getUsername(), 'level' => 'owner' ) );

			// Cache miss, save the data
			SpamFilter_Panel_Cache::set($cacheKey, $accounts);
		}

		// Proceed
		if( (!isset($accounts)) || (empty($accounts)) || (is_countable($accounts) && count($accounts) == 0) )
		{
			$this->_flashMessenger->addMessage(
							array(
								'message' => $this->t->_('Unable to retrieve accounts.'),
								'status' => 'error'
								)
							);
			return false;
		}

	        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_Array($accounts));
	        $paginator->setItemCountPerPage(25)
        	          ->setCurrentPageNumber($this->_getParam('page', 1));
        	$this->view->paginator = $paginator;
	}
//

	public function toggleuserAction()
	{
		$user = $this->_getParam('user');	//@TODO: Add a filter to protect the input data (just to be sure)
		$state = $this->_getParam('state');	//@TODO: Add a filter to protect the input data (just to be sure)
		$this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);

		switch($state)
		{
			case "enable":
				// Protect all domains for $user
				$newstatus = "protected";
			break;

			case "disable":
				// Unprotect all domains for $user
				$newstatus = "unprotected";

				$hook = new SpamFilter_Hooks;
				$status = $hook->DeleteAccount($user);
			break;
		}

		// Report back the status
		if (empty($status['status']))
		{
			$this->_flashMessenger->addMessage(
							array(
								'message' => sprintf($this->t->_('The protection status of account %s could not be changed to <strong>%s</strong>'), $user, $newstatus),
								'status' => 'error'
								)
							);
		} else {
			$this->_flashMessenger->addMessage(
							array(
								'message' => sprintf($this->t->_('The protection status of account %s has been changed to <strong>%s</strong>'), $user, $newstatus),
								'status' => 'success'
								)
							);
		}
		// Return to the overview.
		$url = $_SERVER['SCRIPT_NAME'] . '?q=' . $this->view->url(array(
										'controller' 	=>	'reseller',
										'action'	=>	'listaccounts',
										'user'		=>	null,
										'state'		=>	null
									));
		$this->_redirect( $url );
		return true;
	}

	public function toggleprotectionAction()
	{
		// @TODO: We need to add some (frontend) confirmation whether they intend to to this. They should know the consequences.

		## Toggle the status, we have to know it first
		$domain = mb_strtolower($this->_getParam('domain'), 'UTF-8');	//@TODO: Add a filter to protect the input data (just to be sure)
		$type = strtolower($this->_getParam('type'));
		$owner_domain = mb_strtolower($this->_getParam('owner_domain'), 'UTF-8');
		$owner_user = $this->_getParam('owner_user');

        $config = Zend_Registry::get('general_config');

        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $urlbase = ((false !== stristr($_SERVER['SCRIPT_NAME'], "index.raw")) ? '' : $_SERVER['SCRIPT_NAME']);

        /**
         * Do not process with extra-domains in case of it is disabled in settings
         * @see https://trac.spamexperts.com/ticket/17730
         */
        if (!empty($owner_domain) && !(0 < $config->handle_extra_domains)) {
            $this->_flashMessenger->addMessage(array(
                'message' => $this->t->_("Processing of addon- and parked domains is disabled in settings"),
                'status'  => 'error',
            ));

            $this->_redirect($urlbase . '?q=' . $this->view->url(array(
                'controller' => 'reseller',
                'action'     => 'listdomains',
                'domain'     => null,
            )));

            return true;
        }

        if (!$this->_panel->validateOwnership($domain)) {
            $this->_flashMessenger->addMessage(array(
                 'message' => sprintf($this->t->_("You're not allowed to operate on the domain '%s'"), htmlspecialchars($domain, ENT_QUOTES, 'UTF-8')),
                 'status'  => 'error',
            ));

            $this->_redirect($urlbase . '?q=' . $this->view->url(array(
                'controller' => 'reseller',
                'action'     => 'listdomains',
                'domain'     => null,
            )));

            return true;
        }

		// Execute action
		$in_filter = $this->_panel->isInFilter( $domain );

		$hook = new SpamFilter_Hooks;
		if(! $in_filter )
		{
			// Add to filter
			$status = $this->_panel->bulkProtect( array('domain' => $domain, 'type' => $type, 'owner_domain' => $owner_domain, 'owner_user' => (!empty($owner_user) ? $owner_user : $this->_panel->getDomainUser($domain))) );
			if (!empty($status['reason_status']) && 'ok' == $status['reason_status']) {
				 $status['status'] = true;
			}
			$newstatus = "protected";
		} else {
			// Remove from filter
			if (('parked' == $type || 'addon' == $type) && !empty($owner_domain) && $config->add_extra_alias) {
			    $status = $hook->DelAlias($owner_domain, $domain, true );
			    $newstatus = "unprotected";
			} else {
                // Try to find the domain's aliases
                // @see https://trac.spamexperts.com/software/ticket/13043
                $aliases = array();
                if ($config->add_extra_alias) {
                    $domainOwnerUsername = $this->_panel->getDomainUser($domain);
                    $addonDomains = $this->_panel->getAddonDomains($domainOwnerUsername, $domain);
                    $parkedDomains = $this->_panel->getParkedDomains($domainOwnerUsername, $domain);

                    $secondaryDomains = array();
                    if (is_array($addonDomains) && is_array($parkedDomains)) {
                        $secondaryDomains = @array_merge_recursive($addonDomains, $parkedDomains);
                    } elseif (is_array($addonDomains)) {
                        $secondaryDomains = $addonDomains;
                    } elseif (is_array($parkedDomains)) {
                        $secondaryDomains = $parkedDomains;
                    }

                    foreach ($secondaryDomains as $data) {
                        $aliases[] = $data['alias'];
                    }

                    $aliases = array_unique($aliases, SORT_REGULAR);

                    unset($secondaryDomains);
                }

			    $status = $hook->DelDomain( $domain, true, true, $aliases ); // force removal, reset DNS zone for manual removes

			    $newstatus = "unprotected";
			}
		}

                //Sets default value for status key in $status array
                //@see https://trac.spamexperts.com/ticket/22352
                if(!array_key_exists('status', $status)){
                    $status['status'] = '';
                }

		// Report back the status
		if ( $status['status'] != true )
		{
			switch ( $status['reason'] ) {
	            case SpamFilter_Hooks::ALREADYEXISTS_NOT_OWNER:
                    $reason = $this->t->_(' you are not the owner of it.');
		        break;

		        case SpamFilter_Hooks::SKIP_REMOTE:
                    $reason = $this->t->_(' because domain uses remote exchanger.');
		        break;

		        case SpamFilter_Hooks::SKIP_DATAINVALID:
				    $reason = $this->t->_(' because data is invalid.');
				break;

			    case SpamFilter_Hooks::SKIP_UNKNOWN:
				    $reason = $this->t->_(' unknown error.');
				break;

			    case SpamFilter_Hooks::SKIP_APIFAIL:
				    $reason = $this->t->_(' because API communication failed.');
				break;

			    case SpamFilter_Hooks::SKIP_ALREADYEXISTS:
				    $reason = $this->t->_(' because domain already exists.');
				break;

			    case SpamFilter_Hooks::SKIP_INVALID:
				    $reason = $this->t->_(' because domain is not valid.');
				break;

			    case SpamFilter_Hooks::SKIP_NOROOT:
				    $reason = $this->t->_(' because root domain cannot be added.');
				break;

			    case SpamFilter_Hooks::API_REQUEST_FAILED:
				    $reason = $this->t->_(' because API request has failed.');
				break;

			    case SpamFilter_Hooks::DOMAIN_EXISTS:
				    $reason = $this->t->_(' because domain already exists.');
				break;

			    case SpamFilter_Hooks::ALIAS_EXISTS:
				    $reason = $this->t->_(' because alias already exists.');
				break;

		        case SpamFilter_Hooks::DOMAIN_LIMIT_REACHED:
				    $reason = $this->t->_(' because domain limit reached.');
				break;

	            default:
	                $reason = (!empty($status['additional'])) ? '. ' . ((is_array($status['additional'])) ? implode(', ', $status['additional']) : $status['additional']) : '';
		        break;
            }

			$this->_flashMessenger->addMessage(
							array(
								'message' => sprintf($this->t->_('The protection status of %s could not be changed to <strong>%s</strong>%s'), $domain, $newstatus, $reason),
								'status' => 'error'
								)
							);
		} else {
			$this->_flashMessenger->addMessage(
							array(
								'message' => sprintf($this->t->_('The protection status of %s has been changed to <strong>%s</strong>'), $domain, $newstatus),
								'status' => 'success'
								)
							);
		}

		// Return to the overview.
		$url = $urlbase . '?q=' . $this->view->url(array(
            'controller' => 'reseller',
            'action'     => 'listdomains',
            'domain'     => null)
        );

        $this->_redirect( $url );
		return true;
	}
}
