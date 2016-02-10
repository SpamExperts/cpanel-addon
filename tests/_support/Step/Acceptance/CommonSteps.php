<?php

namespace Step\Acceptance;

use Pages\ConfigurationPage;
use Pages\CpanelClientPage;
use Pages\CpanelWHMPage;
use Pages\DomainListPage;
use Pages\SpampanelPage;

class CommonSteps extends \WebGuy
{
    protected $currentBrandname = 'Professional Spam Filter';

    private static $accounts = array(); // a new instance is created for cleanup ?!
    private static $loggedInAsClient = false;
    private $defaultPackage = 'package1';

    public function loginAsRoot()
    {
        $user = getenv($this->getEnvParameter('username'));
        $pass = getenv($this->getEnvParameter('password'));
        $this->login($user, $pass);
        self::$loggedInAsClient = false;
    }

    public function login($username = null, $password = null)
    {
        if (! $username && ! $password) {
            $this->loginAsRoot();
            return;
        }

        $I = $this;
        $I->amOnUrl(getenv($I->getEnvParameter('url'))); // for cases such as logout from client interface
        $I->wait(2);
        $I->fillField('#user', $username);
        $I->fillField('#pass', $password);
        $I->click('Log in');
        $I->waitForElement('#topFrame');
        $I->switchToTopFrame();
        $I->waitForElement('div.topFrameWrapper div.navigationContainer div.navigation ul.navigationLinks li a.navLink');
        $I->see('Logout');
        $I->switchToWindow();
    }

    public function logout()
    {
        $this->switchToTopFrame();
        $this->click(CpanelWHMPage::LOGOUT);
    }

    public function goToPage($page, $title)
    {
        $I = $this;
        $I->amGoingTo("\n\n --- Go to {$title} page --- \n");
        $I->switchToWindow();
        $I->reloadPage();
        $I->switchToMainFrame();
        $I->waitForText('Plugins');
        $I->click('Plugins');
        $I->waitForText($this->currentBrandname);
        $I->click($this->currentBrandname);
        $I->switchToMainFrame();
        $I->waitForText($this->currentBrandname);
        $I->see($this->currentBrandname);
        $I->waitForText('Configuration');
        $I->click($page);
        $I->waitForText($title);
    }

    public function checkPsfIsPresent()
    {
        $I = $this;
        $I->switchToMainFrame();
        $I->waitForText('Plugins');
        $I->click('Plugins');
        $I->waitForText($this->currentBrandname);
        $I->see($this->currentBrandname);
    }

    public function generateRandomDomainName()
    {
        $domain = uniqid("domain") . ".example.com";
        $this->comment("I generated random domain: $domain");

        return $domain;
    }

    public function generateRandomUserName()
    {
        $username = 'u'.strrev(uniqid()); // cpanel requires first 8 chars to be unique and not start with a number
        $this->comment("I generated random username: $username");

        return $username;
    }

    public function createNewAccounts($numberOfAccounts = 2)
    {
        $accounts = array();

        while ($numberOfAccounts) {
            $accounts[] = $this->createNewAccount();
            $numberOfAccounts--;
        }

        return $accounts;
    }

    /**
     * Create a new account. It sets the default package if it exists.
     * It gives a
     *
     * @param array $params
     * @return array
     */
    public function createNewAccount(array $params = array())
    {
        if (empty($params['domain'])) {
            $params['domain'] = $this->generateRandomDomainName();
        }

        if (empty($params['username'])) {
            $params['username'] = $this->generateRandomUserName();
        }

        if (empty($params['password'])) {
            $params['password'] = uniqid();
        }

        if (empty($params['contactemail'])) {
            $params['contactemail'] = $params['username'].'@'.$params['domain'];
        }

        if (empty($params['reseller'])) {
            $params['reseller'] = false;
        }

        $params['pkgname'] = $this->defaultPackage;
        $params['reseller'] = (int) $params['reseller'];

        $I = $this;
        $I->makeCpanelApiRequest()->addAccount($params);

        if ($params['reseller']) {
            $this->grantAllAccessToReseller($params['username']);
        }

        $account = array(
            'domain' => $params['domain'],
            'username' => $params['username'],
            'password' => $params['password'],
            'email' => $params['contactemail'],
            'reseller' => $params['reseller']
        );

        self::$accounts[] = $account;

        return $account;
    }

    public function grantAllAccessToReseller($username)
    {
        $I = $this;
        $I->searchAndClickCommand('Edit Reseller Nameservers and Privileges');
        $I->selectOption('res', $username);
        $I->click(CpanelWHMPage::RESELLER_ACCESS_GRANT_SUBMIT_BTN);
        $I->checkOption(CpanelWHMPage::RESELLER_ACCESS_ALL_CHECKBOX);
        $I->click('#masterContainer > form > input.btn-primary');
        $I->waitForText('Modified reseller '.$username, 10);
    }

    /**
     * Click a commander link after searching for it
     */
    public function searchAndClickCommand($fullCommand)
    {
        $this->switchToCommanderFrame();
        $this->fillField('#quickJump', $fullCommand);
        $this->click($fullCommand);
        $this->switchToMainFrame();
    }

    public function goToProspamfilterMenu()
    {
        $this->searchAndClickCommand($this->currentBrandname);
        $this->waitForText($this->currentBrandname, 10, 'body > div > header > h1');
    }

    public function goToDomainListPage()
    {
        $this->goToProspamfilterMenu();
        $this->click("//h4[contains(.,'Domain List')]");
        $this->waitForText("List Domains");
    }

    public function switchToMainFrame()
    {
        $this->switchToWindow();
        $this->switchToIFrame('mainFrame');
    }

    public function switchToCommanderFrame()
    {
        $this->switchToWindow();
        $this->switchToIFrame('commander');
    }

    public function switchToTopFrame()
    {
        $this->switchToWindow();
        $this->switchToIFrame('topFrame');
    }

    public function will($message)
    {
        $this->comment("\n\n --- {$message} --- \n");
    }

    public function checkDomainList($domain)
    {
        $this->goToDomainListPage();
        $this->see($domain, DomainListPage::DOMAIN_TABLE);
    }

    public function searchDomainList($domain)
    {
        $this->goToDomainListPage();
        $this->fillField(DomainListPage::SEARCH_FIELD, $domain);
        $this->click(DomainListPage::SEARCH_BTN);
        $this->waitForText('Page 1 of 1. Total Items: 1');
        $this->see($domain, DomainListPage::DOMAIN_TABLE);
    }

    public function checkDomainIsPresentInFilter($domain)
    {
        $this->searchDomainList($domain);
        $this->click('Check status');
        $this->waitForText('This domain is present in the filter.');
    }

    public function checkDomainIsNotPresentInFilter($domain)
    {
        $this->searchDomainList($domain);
        $this->click('Check status');
        $this->waitForText('This domain is not present in the filter.');
    }

    public function loginAsClient($username, $password)
    {
        $I = $this;
        $I->amOnUrl($I->getClientUrl());
        $I->fillField('#user', $username);
        $I->fillField('#pass', $password);
        $I->click('Log in');
        $I->wait(2);
        $I->waitForText('LOGOUT');
        self::$loggedInAsClient = true;
    }

    public function addAddonDomainAsClient($domain, $addonDomainName = null)
    {
        if (! $addonDomainName) {
            $addonDomainName = 'addon' . $domain;
        }

        $I = $this;
        $I->click('Addon Domains');
        $I->waitForText('Create an Addon Domain');
        $I->fillField('#domain', $addonDomainName);
        $I->click('Add Domain');
        $I->waitForText('The addon domain “'.$addonDomainName.'” has been created.', 30);

        return $addonDomainName;
    }

    public function addSubdomainAsClient($domain, $subDomainPrefix = null)
    {
        if (! $subDomainPrefix) {
            $subDomainPrefix = 'sub';
        }

        $subDomain = $subDomainPrefix.'.'.$domain;
        $I = $this;
        $I->click('Subdomains');
        $I->fillField('#domain', $subDomainPrefix);
        $I->selectOption('#rootdomain', $domain);
        $I->click('Create');
        $I->waitForText('Success: “'.$subDomain.'” has been created.', 30, '.alert-message');

        return $subDomain;
    }

    public function addParkedDomainAsClient($domain, $parkedDomain = null)
    {
        if (! $parkedDomain) {
            $parkedDomain = 'parked'.$domain;
        }

        $I = $this;
        $I->changeToX3Theme();
        $I->click('#item_parkeddomains');
        $I->fillField('#domain', $parkedDomain);
        $I->click('Add Domain');
        $I->waitForText('The system has successfully created the “'.$parkedDomain.'” parked domain.');
        $I->click(CpanelClientPage::X3_HOME_MENU_LINK);
        $I->changeToPaperLanternTheme();

        return $parkedDomain;
    }

    public function loginOnSpampanel($domain)
    {
        $I = $this;
        $href = $I->grabAttributeFrom('//a[contains(text(), "Login")]', 'href');
        $I->amOnUrl($href);
        $I->waitForText("Welcome to the $domain control panel", 60);
        $I->see("Logged in as: $domain");
        $I->see("Domain User");
    }

    public function addRouteInSpampanel($route, $port = 25)
    {
        $I = $this;
        $I->click('Edit route(s)');
        $I->click('Add a route');
        $I->fillField('#route_host_new', $route);
        $I->fillField('#route_port_new', $port);
        $I->click('#submit_new_route_btn');
        $I->see('Domain routes updated successfully');
    }

    public function logoutFromSpampanel()
    {
        $this->waitForElementVisible(SpampanelPage::LOGOUT_LINK);
        $this->click(SpampanelPage::LOGOUT_LINK);
        $this->waitForElementVisible(SpampanelPage::LOGOUT_CONFIRM_LINK);
        $this->click(SpampanelPage::LOGOUT_CONFIRM_LINK);
    }

    public function checkDomainListAsClient($domain)
    {
        $I = $this;
        $I->see($this->currentBrandname);
        $I->click($this->currentBrandname);
        $I->waitForText('List Domains');
        $I->see('This page shows you a list of all domains owned by you.');
        $I->seeInDomainTable($domain);
    }

    public function seeInDomainTable($domain)
    {
        $this->see($domain, '#domainoverview');
    }

    public function dontSeeInDomainTable($domain)
    {
        $this->dontSee($domain, '#domainoverview');
    }

    public function logoutAsClient()
    {
        $paperLanternLink = $this->getElementsCount(CpanelClientPage::PAPER_LANTERN_LOGOUT_LINK);

        if ($paperLanternLink) {
            $this->click(CpanelClientPage::PAPER_LANTERN_LOGOUT_LINK);
        } else {
            $this->click(CpanelClientPage::X3_LOGOUT_LINK);
        }
    }

    public function removeCreatedAccounts()
    {
        $this->will("Remove created accounts");

        $usernames = array_map(function ($account) {return $account['username'];}, self::$accounts);

        if (! $usernames) {
            $this->comment("No created accounts to remove!");
            return;
        }

        $this->removeAccounts($usernames);
    }

    public function removeAccount($username)
    {
        $this->removeAccounts(array($username));
    }

    public function removeAccounts(array $usernames)
    {
        $this->will("Terminate accounts: ".implode(', ', $usernames));
        foreach ($usernames as $username) {
            $this->makeCpanelApiRequest()->deleteAccount($username);
            $this->removeAccountByUsername($username);
        }
    }

    public function removeAllAccounts()
    {
        $this->will("Terminate ALL accounts");
        $this->searchAndClickCommand('Terminate Multiple Accounts');
        $selector = "//input[@type='checkbox'][contains(@name, 'acct')]";
        $this->checkAllBoxes($selector);
        $this->fillField('//*[@id="masterContainer"]/form/input[@name="verify"]', "I understand this will irrevocably remove all the accounts that have been checked");
        $this->click("Destroy Selected Accounts");
        $this->waitForElementNotVisible('#waitpanel_mask', 240);
    }

    public function getEnvHostname()
    {
        $url = getenv($this->getEnvParameter('url'));
        $parsed = parse_url($url);

        if (empty($parsed['host'])) {
            throw new \Exception("Couldnt parse url");
        }

        return $parsed['host'];
    }

    public function getMxEntriesFromCpanelInterface($domain)
    {
        $I = $this;
        $I->searchAndClickCommand('Edit MX Entry');
        $I->selectOption('domainselect', $domain);
        $I->click('Edit');
        $I->wait(2);
        $values = $I->getElementsValues('#mxlist input.mx');

        return $values;
    }

    public function setDefaultConfigurationOptions()
    {
        $this->setConfigurationOptions($this->getDefaultConfigurationOptions());
    }

    public function setConfigurationOptions(array $options)
    {
        $options = array_merge($this->getDefaultConfigurationOptions(), $options);

        foreach ($options as $option => $check) {
            if ($check) {
                $this->checkOption($option);
            } else {
                $this->uncheckOption($option);
            }
        }

        $this->click(ConfigurationPage::SAVE_SETTINGS_BTN);
        $this->see('The settings have been saved.');
    }

    public function createDefaultPackage()
    {
        $I = $this;
        $I->searchAndClickCommand('Delete a Package');

        $I->wait(2);
        $count = $I->getElementsCount("//select/option[@value='$this->defaultPackage']");

        if ($count) {
            $this->comment("Default package already created");
            return;
        }

        $I->searchAndClickCommand('Add a Package');
        $I->fillField('fieldset > div > div > div.propertyValue > input', $this->defaultPackage);
        $I->click('#maxpark_unlimited_radio');
        $I->click('#maxaddon_unlimited_radio');
        $I->click('Add');
        $I->waitForElementNotVisible('div.mask', 60);
    }

    public function changeToX3Theme()
    {
        $this->clickOneOf([CpanelClientPage::DASHBOARD_LINK, CpanelClientPage::HOME_MENU_LINK_V52]);
        $this->selectOption('#ddlChangeTheme', 'x3');
        $this->waitForText($this->currentBrandname, 30);
    }

    public function changeToPaperLanternTheme()
    {
        $this->selectOption('//select[@name="theme"]', 'paper_lantern');
        $this->waitForText($this->currentBrandname, 30);
    }

    private function getDefaultConfigurationOptions()
    {
        return array(
            ConfigurationPage::ENABLE_SSL_FOR_API_OPT => false,
            ConfigurationPage::ENABLE_AUTOMATIC_UPDATES_OPT => true,
            ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT => true,
            ConfigurationPage::AUTOMATICALLY_DELETE_DOMAINS_OPT => true,
            ConfigurationPage::AUTOMATICALLY_CHANGE_MX_OPT => true,
            ConfigurationPage::CONFIGURE_EMAIL_ADDRESS_OPT => true,
            ConfigurationPage::PROCESS_ADDON_CPANEL_OPT => true,
            ConfigurationPage::ADD_ADDON_CPANEL_OPT => false,
            ConfigurationPage::USE_EXISTING_MX_OPT => true,
            ConfigurationPage::DO_NOT_PROTECT_REMOTE_DOMAINS_OPT => true,
            ConfigurationPage::REDIRECT_BACK_TO_CPANEL_OPT => false,
            ConfigurationPage::ADD_DOMAIN_DURING_LOGIN_OPT => true,
            ConfigurationPage::FORCE_CHANGE_MX_ROUTE_OPT => false,
            ConfigurationPage::CHANGE_EMAIL_ROUTING_OPT => true,
            ConfigurationPage::ADD_REMOVE_DOMAIN => false,
            ConfigurationPage::DISABLE_ADDON_IN_CPANEL => false,
            ConfigurationPage::USE_IP_AS_DESTINATION_OPT => false,
            ConfigurationPage::SET_SPF_RECORD => false,
        );
    }

    private function getClientUrl()
    {
        $url = getenv($this->getEnvParameter('url'));

        return str_replace('2087', '2083', $url);
    }

    private function removeAccountByUsername($username)
    {
        foreach (self::$accounts as $i => $account) {
            if ($username == $account['username']) {
                unset(self::$accounts[$i]);
                return;
            }
        }
    }
}