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
* @since     2.5
*/
/** Zend_Controller_Action */
class DomainController extends Zend_Controller_Action
{
    /** @var SpamFilter_Acl */
    protected $_acl;

	protected $_config;

    /** @var Zend_Translate_Adapter_Gettext */
    var $t;

    /**
     * Flash messages manager instance
     *
     * @access protected
     * @var SpamFilter_Controller_Action_Helper_FlashMessenger
     */
    protected $_flashMessenger;

    protected $_hasAPIAccess;

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

        if (!Zend_Registry::isRegistered('general_config')) {
			Zend_Registry::get('logger')->debug("[DomainController] Initializing settings.. ");
			$settings = new SpamFilter_Configuration( CFG_PATH . DS . 'settings.conf' ); // <-- General settings
		}
		$this->_config	= Zend_Registry::get('general_config');
	}

	public function preDispatch()
	{
		// Setup ACL
		$this->_acl = new SpamFilter_Acl();

		$username = SpamFilter_Core::getUsername();

		// Retrieve usertype using the Panel driver
		$panel = new SpamFilter_PanelSupport( );
		$userlevel = $panel->getUserLevel();

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
        if (!$brandname){
            $brandname = 'Professional Spam Filter';
        }

		$this->view->headTitle()->set($brandname);
		$this->view->headTitle()->setSeparator(' | ');

		$this->view->headStyle()->appendStyle( file_get_contents(BASE_PATH . DS . 'public' . DS . 'css' . DS . 'bootstrap.min.css') );
		$this->view->headStyle()->appendStyle( file_get_contents(BASE_PATH . DS . 'public' . DS . 'css' . DS . 'bootstrap-responsive.min.css') );
		$this->view->headStyle()->appendStyle( file_get_contents(BASE_PATH . DS . 'public' . DS . 'css' . DS . 'addon.css') );
		$this->view->headScript()->appendScript( file_get_contents(BASE_PATH . DS . 'public' . DS . 'js' . DS . 'jquery.min.js') );
		$this->view->headScript()->appendScript( file_get_contents(BASE_PATH . DS . 'public' . DS . 'js' . DS . 'bootstrap.min.js') );

		$this->view->acl = $this->_acl;
        $this->view->t = $this->t;
        $this->view->brandname = $brandname;
        $this->view->hasAPIAccess = $this->_hasAPIAccess = $branding->hasAPIAccess();
    }

	public function indexAction()
	{
        if (!$this->_hasAPIAccess) { return;}
        /** @var $panel SpamFilter_PanelSupport_Cpanel */
        $panel = new SpamFilter_PanelSupport;

        /** check the role of the owner */
        $ownerRole = "";

        $primaryUsers = $panel->getPrimaryUsers();
        if (!empty($primaryUsers) && is_array($primaryUsers)) {
            foreach ($primaryUsers as $primaryUser) {
                if ($primaryUser['user'] == SpamFilter_Core::getUsername()) {
                    $ownerRole = $panel->getUserLevel($primaryUser['owner']);

                    break;
                }
            }
        }

        if (($panel->getUserLevel() == 'role_reseller' ||  $ownerRole == 'role_reseller') && $panel->isDisabledForResellers()) {
            $this->_flashMessenger->addMessage(
                array(
                    'message' => $this->t->_('This addon is not available at your access level.'),
                    'status' => 'info',
                )
            );

            return false;
        } else {
            $panel = new SpamFilter_Panelsupport();
            if (!$panel->resellerHasFeatureEnabled(SpamFilter_Core::getUsername())) {
                $this->_flashMessenger->addMessage(
                    array(
                        'message' => $this->t->_('This feature is not available for your account'),
                        'status' => 'info',
                    )
                );

                return false;
            }
        }

        // Proceed
        $cacheKey = strtolower('user_domains_' . md5(SpamFilter_Core::getUsername()));
        $domains = SpamFilter_Panel_Cache::get( $cacheKey );

		// No cache set, proceed with retrieval
		if (!$domains) {
            $user = SpamFilter_Core::getUsername();
            $level = ($panel->getUserLevel($user) == 'role_enduser') ? 'user' : 'owner';
			$domains = $panel->getDomains(array('username' => $user, 'level' => $level));
			// Cache miss, save the data
			SpamFilter_Panel_Cache::set($cacheKey, $domains);
		}

        // Proceed
        if (!isset($domains)) {
			$this->_flashMessenger->addMessage(
                array(
                    'message' => $this->t->_('Unable to retrieve domains.'),
                    'status' => 'error',
                )
            );

			return false;
		}

        if (empty($domains)) {
			$this->_flashMessenger->addMessage(
                array(
                    'message' => $this->t->_('There are no domains on this server.'),
                    'status' => 'info',
                )
            );

			return false;
		}

		$paginator = new Zend_Paginator(new Zend_Paginator_Adapter_Array($domains));
		$paginator->setItemCountPerPage(25)
				  ->setCurrentPageNumber($this->_getParam('page', 1));
		$this->view->paginator = $paginator;

        if (SpamFilter_Core::isCpanel() && !empty($_GET['paneltype']) && 'cpanel' == $_GET['paneltype']) {
             $this->view->baseFile = 'psf.php';
        }

        $this->view->accesslevel = strtolower($panel->getUserLevel());
        $this->view->settings = Zend_Registry::get('general_config');
	}

	public function loginAction()
	{
        $domain = mb_strtolower($this->_getParam('domain'), 'UTF-8');
		$type = strtolower($this->_getParam('type'));
		$owner_domain = mb_strtolower($this->_getParam('owner_domain'), 'UTF-8');
		$owner_user = $this->_getParam('owner_user');

        if (empty($domain)) {
            $this->_flashMessenger->addMessage(
                array('message' => $this->t->_('You should provide a domain to login with.'), 'status' => 'error')
            );

			return false;
		}

		/** @var $panel SpamFilter_PanelSupport_Cpanel */
        $panel = new SpamFilter_Panelsupport();

        if (!$this->isValidDomainAndType($domain, $type, $panel)) {
            $this->_forward('index');

            return false;
        }

        if (!$panel->validateOwnership($domain)) {
            $this->_flashMessenger->addMessage(
                array('message' => $this->t->_('This domain does not belong to you.'), 'status' => 'error')
            );

			return false;
		}

        /** Check options if we should prevent creating domain when running manual query */
        if ($type != 'account' &&
            ($this->_config->handle_extra_domains == 0 || $this->_config->add_extra_alias == 1)
        ) {
            $this->_flashMessenger->addMessage(
                array('message' => $this->t->_('You cannot log in. Please check configuration for parked, addon and subdomains.'), 'status' => 'error')
            );
            return false;
        }

		// At this point, we have validated everything and are ready to login.
		// But, we still need to retrieve the AUTH key using the API. Easy peasy.

		$api = new SpamFilter_ResellerAPI();
		$login_request = array();

		if ($this->_config->add_extra_alias) {
			$login_request['username'] = (!empty($owner_domain)) ? $owner_domain : $domain;
		} else {
		    $login_request['username'] = $domain;
		}

        $owner_user = !empty($owner_user) ? $owner_user : $panel->getDomainUser($domain);

		// Check if we have the option to redirect back.
        if ($this->_config->redirectback) {
			$request = $this->getRequest();

            $host = $request->getHttpHost();
            if (!strstr($host, ":")) {
                Zend_Registry::get('logger')->debug("[DomainController] There is no port in the URL, adding...");
                $host .= ":" . $_SERVER['SERVER_PORT'];
            }

            $logouturl = $request->getScheme() . '://' . $host;
			Zend_Registry::get('logger')->debug("[DomainController] Setting logout URL to '{$logouturl}' ");

            if (!empty($_ENV['cp_security_token'])) {
                $logouturl .= "{$_ENV['cp_security_token']}/";
            }
            if ($this->isValidUrl($logouturl)) {
                $login_request['logouturl'] = base64_encode( $logouturl );
            } else {
                Zend_Registry::get('logger')->debug("[DomainController] The logout URL is invalid. Failed to set the logout URL to '{$logouturl}' ");
            }

		}

        $protectstatus = null;

        $isInFilter = $panel->isInFilter( $domain );
        $setDomainuserEmail = false;

        if ($this->_config->add_domain_loginfail && false === $isInFilter) {
            if (!empty($owner_domain) && !empty($type) && !$panel->isInFilter($owner_domain)
                && ($type != 'domain' || $type != 'account')
                && $this->_config->add_extra_alias
            ) {
                $this->_flashMessenger->addMessage(
                    array(
                        'message' => sprintf($this->t->_('Unable to login. %s can not be added as an alias, because the main domain %s is not present in the filter.'), $domain, $owner_domain),
                        'status' => 'error')
                );

                $this->_forward('index');
		    }

            // Add to filter
            $protectstatus = $panel->bulkProtect(array(
                 'domain'       => $domain,
                 'type'         => $type,
                 'owner_domain' => $owner_domain,
                 'owner_user'   => $owner_user,
            ));

            $setDomainuserEmail = true;
		}

        $authTicket = null;
        if (true === $isInFilter || 'ok' == $protectstatus['reason_status']) {
		    $authTicket = $api->authticket()->create($login_request);
            /** @see https://trac.spamexperts.com/ticket/19397 */
            if (is_scalar($authTicket)) {
                $authTicket = array(
                    'result' => $authTicket,
                );
            }

            if ($setDomainuserEmail) {
                /**
                 * Set email for the new domain user
                 * @see https://trac.spamexperts.com/ticket/17743
                 */
            }
        } elseif (is_array($isInFilter['additional'])) {
            $this->_flashMessenger->addMessage(
                array(
                    'message' => $this->t->_('Unable to login, possibly professional spam and virus filtering has not been enabled for your domain yet. Please contact your provider for options to activate this service.'),
                    'status' => 'error',
                )
            );

            foreach ($isInFilter['additional'] as $msg) {
				$this->_flashMessenger->addMessage(array(
                    'message' => $msg,
                    'status' => 'error',
                ));
			}

            $this->_forward('index');

            return false;
        }

		if (empty($authTicket['result']) || !preg_match('~^[0-9a-f]{40}$~i', $authTicket['result'])) {
            // Failure
            $this->_flashMessenger->addMessage(
                array(
                    'message' => $this->t->_('Unable to login, possibly professional spam and virus filtering has not been enabled for your domain yet. Please contact your provider for options to activate this service.'),
                    'status' => 'error',
                )
            );

            if (!empty($authTicket['additional']) && is_array($authTicket['additional'])) {
                $this->_flashMessenger->addMessage(
                    array(
                        'message' => "<strong>Additional information:</strong>",
                        'status' => 'error',
                    )
                );
                foreach ($authTicket['additional'] as $msg) {
                    $this->_flashMessenger->addMessage(array('message' => $msg, 'status' => 'error'));
                }
            }

            if (!empty($protectstatus['reason'])) {
                $this->_flashMessenger->addMessage(array('message' => $protectstatus['reason'], 'status' => 'error'));
            }

            /** @see https://trac.spamexperts.com/ticket/24320 */
            if (stripos($_SERVER['HTTP_REFERER'], '/frontend/paper_lantern/')) {
                $this->_redirect($_SERVER['HTTP_REFERER']);
            } else {
                $this->_forward('index');
            }
		} else {

            // We do not want any "headers already sent" stuff, so disable the whole layout here.
            $this->_helper->layout()->disableLayout();
            $this->_helper->viewRenderer->setNoRender(true);
            $url = $this->_config->spampanel_url . "/?authticket=" . $authTicket['result'];

            Zend_Registry::get('logger')->debug("[DomainController] Logging in with url '{$url}' ");

            $this->_redirect($url);
        }
	}

    /**
     * Function for validating url
     *
     * @static
     * @access public
     * @param string $url
     * @return bool
     */
    public static function isValidUrl($url)
    {
        // Scheme
        $regex = "^(https?|ftp)\\:\\/\\/";

        // User and pass (optional)
        $regex .= "([a-z0-9+!*(),;?&=\$_.-]+(\\:[a-z0-9+!*(),;?&=\$_.-]+)?@)?";

        // Hostname or ip
        $regex .= "[a-z0-9+\$_-]+(\\.[a-z0-9+\$_-]+)+";  // http://x.x = minimum

        // Port (optional)
        $regex .= "(\\:[0-9]{2,5})?";

        // Path  (optional)
        $regex .= "(\\/([a-z0-9+\$_-]\\.?)+)*\\/?";

        // GET Query (optional)
        $regex .= "(\\?[a-z+&\$_.-][a-z0-9;:@/&%=+\$_.-]*)?";

        // Anchor (optional)
        $regex .= "(#[a-z_.-][a-z0-9+\$_.-]*)?\$";

        return preg_match("~$regex~i", $url);
    }

    /**
     * Check if user has current domain and the type is valid
     *
     * @param $domain
     * @param $type
     * @param SpamFilter_Panelsupport $panel
     *
     * @return bool
     */
    private function isValidDomainAndType($domain, $type, SpamFilter_Panelsupport $panel)
    {
        /** @var $panel SpamFilter_PanelSupport_Cpanel */

        $domains = SpamFilter_Panel_Cache::get(SpamFilter_Core::getDomainsCacheId());
        // No cache set, proceed with retrieval
        if (!$domains) {
            $user = SpamFilter_Core::getUsername();
            $level = ($panel->getUserLevel($user) == 'role_enduser') ? 'user' : 'owner';
            $domains = $panel->getDomains(array('username' => $user, 'level' => $level));
            SpamFilter_Panel_Cache::set(SpamFilter_Core::getDomainsCacheId(), $domains);
        }

        $domainType = null;
        $domainName = null;

        if (is_array($domains)) {
            foreach ($domains as $domainData) {
                if ($domainData['domain'] == $domain) {
                    $domainType = $domainData['type'];
                    $domainName = $domain;
                }
            }
        }

        if (! $domainName) {
            $this->_flashMessenger->addMessage(array('message' => $this->t->_('This domain does not exist.'), 'status' => 'error'));

            return false;
        }

        if ($domainType != $type) {
            $this->_flashMessenger->addMessage(array('message' => $this->t->_('There is no domain with given type.'), 'status' => 'error'));

            return false;
        }

        return true;
    }
}
