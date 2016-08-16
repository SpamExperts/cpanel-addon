<?php

namespace Step\Acceptance;

use Page\ConfigurationPage;
use Page\CpanelClientPage;
use Page\CpanelWHMPage;
use Page\DomainListPage;
use Page\ProfessionalSpamFilterPage;
use Page\SpampanelPage;
use Page\CpanelWHMLoginPage;
use Page\BulkprotectPage;
use Codeception\Util\Locator;

class CommonSteps extends \WebGuy
{
    // Current addon name
    protected $currentBrandname = 'Professional Spam Filter';

    // Used for save created accounts and to cleanup them when finish the test
    private static $accounts = array();

    // Used to check if logged in as client
    private static $loggedInAsClient = false;

    // Used to save default package name
    private $defaultPackage = 'package1';

    /**
     * Function used to login as root
     */
    public function loginAsRoot()
    {
        // Get root credentials from environment variables
        $user = getenv($this->getEnvParameter('username'));
        $pass = getenv($this->getEnvParameter('password'));

        // Login with those credentials
        $this->login($user, $pass);

        // I am not logged in as client
        self::$loggedInAsClient = false;
    }

    /**
     * Function used to login, if no credentials provided will login as root
     * @param string $username - username
     * @param string $password - password
     */
    public function login($username = null, $password = null)
    {
        // If no credentials provided, login as root
        if (!$username && !$password) {
            $this->loginAsRoot();
            return;
        }

        // Go to login page
        $this->amOnUrl(getenv($this->getEnvParameter('url')));

        // Fill the username field
        $this->waitForElement(Locator::combine(CpanelWHMLoginPage::USERNAME_FIELD_XPATH, CpanelWHMLoginPage::USERNAME_FIELD_CSS), 10);
        $this->fillField(Locator::combine(CpanelWHMLoginPage::USERNAME_FIELD_XPATH, CpanelWHMLoginPage::USERNAME_FIELD_CSS), $username);

        // Fill password field
        $this->waitForElement(Locator::combine(CpanelWHMLoginPage::PASSWORD_FIELD_XPATH, CpanelWHMLoginPage::PASSWORD_FIELD_CSS), 10);
        $this->fillField(Locator::combine(CpanelWHMLoginPage::PASSWORD_FIELD_XPATH, CpanelWHMLoginPage::PASSWORD_FIELD_CSS), $password);

        // Click the login button
        $this->click("Log in");

        // Wait for all frames to show
        $this->waitForElement(Locator::combine(CpanelWHMPage::TOP_FRAME_XPATH, CpanelWHMPage::TOP_FRAME_CSS), 10);
        $this->waitForElement(Locator::combine(CpanelWHMPage::COMMANDER_FRAME_XPATH, CpanelWHMPage::COMMANDER_FRAME_CSS), 10);
        $this->waitForElement(Locator::combine(CpanelWHMPage::MAIN_FRAME_XPATH, CpanelWHMPage::MAIN_FRAME_CSS), 10);
    }

    /**
     * Function used to logout from cPanel if logged in as root
     */
    public function logout()
    {
        // Switch to top frame
        $this->switchToTopFrame();

        // Click logout button
        $this->click(CpanelWHMPage::LOGOUT_BTN);
    }

    /**
     * Function used to logout as client
     */
    public function logoutAsClient()
    {
        $this->waitForElement(Locator::combine(CpanelClientPage::CLIENT_LOGOUT_BTN_XPATH, CpanelClientPage::CLIENT_LOGOUT_BTN_CSS), 10);
        $this->click(Locator::combine(CpanelClientPage::CLIENT_LOGOUT_BTN_XPATH, CpanelClientPage::CLIENT_LOGOUT_BTN_CSS));

    }

    /**
     * Function used to go to certain plugin page
     * @param $page - page name
     * @param $title - page title
     */
    public function goToPage($page, $title)
    {
        $this->amGoingTo("\n\n --- Go to {$title} page --- \n");
        $this->switchToWindow();
        $this->reloadPage();
        $this->switchToMainFrame();
        $this->waitForText('Plugins');
        $this->click('Plugins');
        $this->waitForText($this->currentBrandname);
        $this->click($this->currentBrandname);
        $this->switchToMainFrame();
        $this->waitForText($this->currentBrandname);
        $this->see($this->currentBrandname);
        $this->waitForText('Configuration');
        $this->click($page);
        $this->waitForText($title);
    }


    /**
     * Function used to go to addon home page
     */
    public function clickCurrentBrandname()
    {
        $this->click($this->currentBrandname);
    }

    /**
     * Function used to check if addon is installed
     */
    public function checkPsfIsPresent()
    {
        $this->searchAndClickCommand('Plugins');
        $this->waitForText($this->currentBrandname);
        $this->see($this->currentBrandname);
    }

    /**
     * Function used to generate random domain name
     * @return string - domain
     */
    public function generateRandomDomainName()
    {
        $domain = uniqid("domain") . ".example.net";
        $this->comment("I generated random domain: $domain");

        return $domain;
    }

    /**
     * Function used to generate random username
     * @return string - domain
     */
    public function generateRandomUserName()
    {
        // cPanel requires first 8 chars to be unique and not start with a number
        $username = 'u'.strrev(uniqid());
        $this->comment("I generated random username: $username");

        return $username;
    }

    /**
     * Function used to create a list of accounts. By default is creating 2 accounts
     * @param int $numberOfAccounts - number of accounts to create
     * @return array - array of accounts
     */
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
     * Function used to create a new account. It sets the default package if exists. Alo is saving the new account in the
     * global static array $accounts
     * @param array $params - account details
     * @return array - array with account details
     */
    public function createNewAccount(array $params = array())
    {
        // If no domain is set, generate a random one
        if (empty($params['domain']))
            $params['domain'] = $this->generateRandomDomainName();

        // If no username is set, generate a random one
        if (empty($params['username']))
            $params['username'] = $this->generateRandomUserName();

        // If no password is set, generate a random one
        if (empty($params['password']))
            $params['password'] = uniqid();

        // If no email is set, generate a random one based on username and domain
        if (empty($params['contactemail']))
            $params['contactemail'] = $params['username'].'@'.$params['domain'];

        // By default the account is not reseller
        if (empty($params['reseller']))
            $params['reseller'] = false;

        // By default ui parameter is false mean that account is created using cPanel API
        if (empty($params['ui']))
            $params['ui'] = false;

        $params['pkgname'] = $this->defaultPackage;
        $params['reseller'] = (int) $params['reseller'];

        // If i want to create account using the UI
        if ($params['ui']) {

            // Go to create new account page
            $this->searchAndClickCommand("Create a New Account");

            // Complete the fields for the new account
            $this->fillField(Locator::combine(CpanelWHMPage::DOMAIN_FIELD_XPATH, CpanelWHMPage::DOMAIN_FIELD_CSS), $params['domain']);
            $this->fillField(Locator::combine(CpanelWHMPage::USERNAME_FIELD_XPATH, CpanelWHMPage::USERNAME_FIELD_CSS), $params['username']);
            $this->fillField(Locator::combine(CpanelWHMPage::PASSWORD_FIELD_XPATH, CpanelWHMPage::PASSWORD_FIELD_CSS), $params['password']);
            $this->wait(2);
            $this->fillField(Locator::combine(CpanelWHMPage::RE_PASSWORD_FIELD_XPATH, CpanelWHMPage::RE_PASSWORD_FIELD_CSS), $params['password']);
            $this->wait(2);
            $this->fillField(Locator::combine(CpanelWHMPage::EMAIL_FIELD_XPATH, CpanelWHMPage::EMAIL_FIELD_CSS), $params['contactemail']);

            // Choose default package for the account
            $this->selectOption(Locator::combine(CpanelWHMPage::CHOOSE_PKG_DROP_DOWN_XPATH, CpanelWHMPage::CHOOSE_PKG_DROP_DOWN_CSS), "package1");

            // If i want the account to be a reseller
            if ($params['reseller'])
                $this->checkOption(Locator::combine(CpanelWHMPage::MAKE_RESELLER_OPT_XPATH, CpanelWHMPage::MAKE_RESELLER_OPT_XPATH));

            // Click the create account button
            $this->click("Create");

            // Wait for account creation to finish
            $this->waitForText("Account Creation Status: ok (Account Creation Ok)", 200, '#masterContainer');

            // If the new account is a reseller grant all access
            if ($params['reseller'])
                $this->grantAllAccessToReseller($params['username']);

        } else {

            // Make a cPanel API request for creating an account
            $this->makeCpanelApiRequest()->addAccount($params);

            // If the new account is a reseller grant all access
            if ($params['reseller']) {
                $this->grantAllAccessToReseller($params['username']);
            }
        }

        // Save the new account details in a array
        $account = array(
            'domain' => $params['domain'],
            'username' => $params['username'],
            'password' => $params['password'],
            'email' => $params['contactemail'],
            'reseller' => $params['reseller']
        );

        // Save the created account in the account list
        self::$accounts[] = $account;

        // Return the previous array
        return $account;
    }

    /**
     * Function used to grant root access to a reseller (all features are enabled)
     * @param $username - reseller username
     */
    public function grantAllAccessToReseller($username)
    {
        // Go to edit reseller nameservers and privileges page
        $this->searchAndClickCommand('Edit Reseller Nameservers and Privileges');

        // Select the desired username from list
        $this->selectOption(Locator::combine(CpanelWHMPage::RESELLER_LIST_XPATH, CpanelWHMPage::RESELLER_LIST_CSS), $username);

        // Click submit button
        $this->click(Locator::combine(CpanelWHMPage::RESELLER_SUBMIT_BTN_XPATH, CpanelWHMPage::RESELLER_SUBMIT_BTN_CSS));

        // Check Root Access All features option
        $this->executeJS("document.getElementById('acl_group_everything').childNodes[1].childNodes[0].checked=true");

        // Click the Save All Settings button
        $this->click("Save All Settings");

        // Wait for settings to be modified
        $this->waitForText('Modified reseller '.$username, 10);
    }

    /**
     * Function used to search for a command in the search box and click on it
     * @param $fullCommand - command name
     */
    public function searchAndClickCommand($fullCommand)
    {
        // Switch to che command iframe
        $this->switchToCommanderFrame();

        // Fill the search box with the desired command name
        $this->fillField(Locator::combine(CpanelWHMPage::SEARCH_BAR_XPATH, CpanelWHMPage::SEARCH_BAR_CSS), $fullCommand);

        // Click on the searched command from the commander frame
        $this->click($fullCommand);

        // Switch back to main frame
        $this->switchToMainFrame();
    }

    /**
     * Function used to search for the spam plugin in the search box and click on it
     */
    public function goToProspamfilterMenu()
    {
        // Search for the current brand name
        $this->searchAndClickCommand($this->currentBrandname);

        // Wait for plugin to load
        $this->waitForText($this->currentBrandname, 10, 'body > div > header > h1');
    }

    /**
     * Function used to go on the domain list page
     */
    public function goToDomainListPage()
    {
        $this->goToProspamfilterMenu();
        $this->click("//h4[contains(.,'Domain List')]");
        $this->waitForText("List Domains");
    }

    /**
     * Function used to switch focus to commander frame
     */
    public function switchToCommanderFrame()
    {
        $this->switchToWindow();
        $this->switchToIFrame(CpanelWHMPage::COMMANDER_FRAME_NAME);
    }

    /**
     * Function used to switch focus to top frame
     */
    public function switchToTopFrame()
    {
        $this->switchToWindow();
        $this->switchToIFrame(CpanelWHMPage::TOP_FRAME_NAME);
    }

    /**
     * Function used to switch focus to main frame
     */
    public function switchToMainFrame()
    {
        $this->switchToWindow();
        $this->switchToIFrame(CpanelWHMPage::MAIN_FRAME_NAME);
    }

    /**
     * Function used to print info messages
     * @param $message - message to be printed
     */
    public function will($message)
    {
        $this->comment("\n\n --- {$message} --- \n");
    }

    /**
     * Function used to search a domain in the domain list
     * @param $domain - domain to search
     */
    public function searchDomainList($domain)
    {
        $this->goToDomainListPage();
        $this->fillField(Locator::combine(DomainListPage::SEARCH_FIELD_XPATH, DomainListPage::SEARCH_FIELD_CSS), $domain);
        $this->click(Locator::combine(DomainListPage::SEARCH_BTN_XPATH, DomainListPage::SEARCH_BTN_CSS));
        $this->see($domain, Locator::combine(DomainListPage::DOMAIN_TABLE_XPATH, DomainListPage::DOMAIN_TABLE_CSS));
    }

    public function searchDomainNotinList($domain)
    {
        $this->goToDomainListPage();
        $this->fillField(Locator::combine(DomainListPage::SEARCH_FIELD_XPATH, DomainListPage::SEARCH_FIELD_CSS), $domain);
        $this->click(Locator::combine(DomainListPage::SEARCH_BTN_XPATH, DomainListPage::SEARCH_BTN_CSS));
        $this->dontSee($domain, Locator::combine(DomainListPage::DOMAIN_TABLE_XPATH, DomainListPage::DOMAIN_TABLE_CSS));
    }

    /**
     * Function used to check if a domain is preseent in filter
     * @param $domain - domain to check
     */
    public function checkDomainIsPresentInFilter($domain)
    {
        $this->searchDomainList($domain);
        $this->checkProtectionStatusIs(DomainListPage::STATUS_DOMAIN_IS_PRESENT_IN_THE_FILTER);
    }

    /**
     * Function used to check if a domain is not present in filter
     * @param $domain - domain to check
     */
    public function checkDomainIsNotPresentInFilter($domain)
    {
        $this->searchDomainList($domain);
        $this->checkProtectionStatusIs(DomainListPage::STATUS_DOMAIN_IS_NOT_PRESENT_IN_THE_FILTER);
    }

    /**
     * Function used to check if protection status for a domain is the one expected
     * @param $status - expected status
     */
    public function checkProtectionStatusIs($status)
    {
        $this->click(Locator::combine(DomainListPage::CHECK_ALL_DOMAINS_BTN_XPATH, DomainListPage::CHECK_ALL_DOMAINS_BTN_CSS));
        $this->waitForText($status);
    }

    /**
     * Function used to log in as a client
     * @param $username
     * @param $password
     */
    public function loginAsClient($username, $password)
    {
        // Go to client login page
        $this->amOnUrl($this->getClientUrl());

        // Fill the username field
        $this->waitForElement(Locator::combine(CpanelWHMLoginPage::USERNAME_FIELD_XPATH, CpanelWHMLoginPage::USERNAME_FIELD_CSS), 10);
        $this->fillField(Locator::combine(CpanelWHMLoginPage::USERNAME_FIELD_XPATH, CpanelWHMLoginPage::USERNAME_FIELD_CSS), $username);

        // Fill the password field
        $this->waitForElement(Locator::combine(CpanelWHMLoginPage::PASSWORD_FIELD_XPATH, CpanelWHMLoginPage::PASSWORD_FIELD_CSS), 10);
        $this->fillField(Locator::combine(CpanelWHMLoginPage::PASSWORD_FIELD_XPATH, CpanelWHMLoginPage::PASSWORD_FIELD_CSS), $password);

        // Click on the login button
        $this->click("Log in");

        $this->wait(2);
        $this->waitForText('LOGOUT');
        self::$loggedInAsClient = true;
    }

    /**
     * Function used to search and click a command as client
     * @param $fullCommand - command name
     */
    public function searchAndClickCommandAsClient($fullCommand)
    {
        // Go back to home page
        $this->clickHomeMenuLink();

        // Wait for search bar container
        $this->wait(2);
        $this->waitForElement(Locator::combine(CpanelClientPage::SEARCH_BAR_CONTAINER_XPATH, CpanelClientPage::SEARCH_BAR_CONTAINER_CSS), 10);

        // Fill the search box with the desired command name
        $this->waitForElement(Locator::combine(CpanelClientPage::SEARCH_BAR_XPATH, CpanelClientPage::SEARCH_BAR_CSS));
        $this->fillField(Locator::combine(CpanelClientPage::SEARCH_BAR_XPATH, CpanelClientPage::SEARCH_BAR_CSS), $fullCommand);

        // Click on the searched command from the commander frame
        $this->waitForText($fullCommand);
        $this->click($fullCommand);
    }

    /**
     * Function used to add a addon domain as client
     * @param $domain - domain name
     * @param null $addonDomainName - addon domain name
     * @return null|string - addon domain name
     */
    public function addAddonDomainAsClient($domain, $addonDomainName = null)
    {
        // If no addon domain name provided generate one based on domain
        if (!$addonDomainName)
            $addonDomainName = 'addon'.$domain;

        // Search for Addon domains option and click on it
        $this->searchAndClickCommandAsClient("Addon Domains");

        // Wait for Addon domain page to load
        $this->waitForText('Create an Addon Domain');

        // Fill new domain name field
        $this->waitForElement(Locator::combine(CpanelClientPage::NEW_DOMAIN_NAME_FIELD_XPATH, CpanelClientPage::NEW_DOMAIN_NAME_FIELD_CSS), 10);
        $this->fillField(Locator::combine(CpanelClientPage::NEW_DOMAIN_NAME_FIELD_XPATH, CpanelClientPage::NEW_DOMAIN_NAME_FIELD_CSS), $addonDomainName);

        // Fill subdomain field
        $this->waitForElement(Locator::combine(CpanelClientPage::SUBDOMAIN_FIELD_XPATH, CpanelClientPage::SUBDOMAIN_FIELD_CSS), 10);
        $this->fillField(Locator::combine(CpanelClientPage::SUBDOMAIN_FIELD_XPATH, CpanelClientPage::SUBDOMAIN_FIELD_CSS), array_shift(explode(".",$addonDomainName)));

        // Fill document root field
        $this->waitForElement(Locator::combine(CpanelClientPage::DOCUMENT_ROOT_FIELD_XPATH, CpanelClientPage::DOCUMENT_ROOT_FIELD_CSS));
        $this->fillField(Locator::combine(CpanelClientPage::DOCUMENT_ROOT_FIELD_XPATH, CpanelClientPage::DOCUMENT_ROOT_FIELD_CSS), "public_html/".$addonDomainName);

        // Click on Add Domain button
        $this->click('Add Domain');

        // Wait for command to finish
        $this->waitForText('The addon domain “'.$addonDomainName.'” has been created.', 30);

        return $addonDomainName;
    }

    /**
     * Function used to remove an addon domain as client
     * @param $addonDomainName - addon domain to be removed
     */
    public function removeAddonDomainAsClient($addonDomainName)
    {
        // Search for Addon Domains option and click on it
        $this->searchAndClickCommandAsClient('Addon Domains');

        // Wait for Addon Domains page to load
        $this->waitForText('Create an Addon Domain');

        // Search for domain to be removed
        $this->fillField(Locator::combine(CpanelClientPage::ADDON_DOMAIN_SEARCH_BAR_XPATH, CpanelClientPage::ADDON_DOMAIN_SEARCH_BAR_CSS), $addonDomainName);

        // Click the search button
        $this->click(Locator::combine(CpanelClientPage::ADDON_DOMAIN_SEARCH_BTN_XPATH, CpanelClientPage::ADDON_DOMAIN_SEARCH_BTN_CSS));

        // Click the remove button
        $this->click("#lnkRemove_$addonDomainName");

        // Wait for removal confirmation
        $this->waitForText("Are you sure you wish to permanently remove the addon domain “".$addonDomainName."”?");

        // Click the remove button to confirm
        $this->click(Locator::combine(CpanelClientPage::ADDON_DOMAIN_CONFIRM_REMOVE_BTN_XPATH, CpanelClientPage::ADDON_DOMAIN_CONFIRM_REMOVE_BTN_CSS));

        // Wait for confirmation message
        $this->waitForText("The addon domain “".$addonDomainName."” has been removed.", 30);

    }

    /**
     * Function used to add a sub domain as a client
     * @param $domain - domain name
     * @param null $subDomainPrefix - sub domain prefix
     * @return string - sub domain name
     */
    public function addSubdomainAsClient($domain, $subDomainPrefix = null)
    {
        // If no sub domain prefix provided use sub
        if (!$subDomainPrefix)
            $subDomainPrefix = 'sub';

        // Generate subdomain string based on sub domain prefix and domain
        $subDomain = $subDomainPrefix.'.'.$domain;

        // Search for Subdomains option and click it
        $this->searchAndClickCommandAsClient("Subdomains");

        // Wait for Sumdomain page to load
        $this->waitForText('Create a Subdomain');

        // Fill domain field
        $this->waitForElement(Locator::combine(CpanelClientPage::ADD_SUBDOMAIN_FIELD_XPATH, CpanelClientPage::ADD_SUBDOMAIN_FIELD_CSS), 10);
        $this->fillField(Locator::combine(CpanelClientPage::ADD_SUBDOMAIN_FIELD_XPATH, CpanelClientPage::ADD_SUBDOMAIN_FIELD_CSS), $subDomainPrefix);

        // Choose the domain from the drop down
        $this->waitForElement(Locator::combine(CpanelClientPage::ADD_SUBDOMAIN_ROOT_DOMAIN_FIELD_XPATH, CpanelClientPage::ADD_SUBDOMAIN_ROOT_DOMAIN_FIELD_CSS), 10);
        $this->selectOption(Locator::combine(CpanelClientPage::ADD_SUBDOMAIN_ROOT_DOMAIN_FIELD_XPATH, CpanelClientPage::ADD_SUBDOMAIN_ROOT_DOMAIN_FIELD_CSS), $domain);

        // Click Create button
        $this->click('Create');

        // Wait for command to finish
        $this->waitForText('Success: “'.$subDomain.'” has been created.', 30);

        return $subDomain;
    }

    /**
     * Function used to remove a subdomain as client
     * @param $subdomain - subdomain to be removed
     */
    public function removeSubdomainAsClient($subdomain)
    {
        // Search for Subdomain option and click on it
        $this->searchAndClickCommandAsClient('Subdomains');

        // Wait for Subdomains page to load
        $this->waitForText('Create a Subdomain');

        // Search for subdomain to be removed
        $this->fillField(Locator::combine(CpanelClientPage::SUBDOMAIN_SEARCH_BAR_XPATH, CpanelClientPage::SUBDOMAIN_SEARCH_BAR_CSS), $subdomain);

        // Click the search button
        $this->click(Locator::combine(CpanelClientPage::SUBDOMAIN_SEARCH_BTN_XPATH, CpanelClientPage::SUBDOMAIN_SEARCH_BTN_CSS));

        // Click the remove button
        $this->click("#{$subdomain}_lnkRemove");

        // Wait for removal confirmation
        $this->waitForText("Are you sure you wish to permanently remove subdomain “".$subdomain."”?");

        // Click the remove button to confirm
        $this->click(Locator::combine(CpanelClientPage::DELETE_SUBDOMAIN_BTN_XPATH, CpanelClientPage::DELETE_SUBDOMAIN_BTN_CSS));

        // Wait for confirmation message
        $this->waitForText("The subdomain “".$subdomain."” has been successfully removed.", 30);

    }

    /**
     * Function used to add alias domain as client
     * same thing
     * @param $domain - domain name
     * @param null $aliasDomain - alias domain name
     * @return null|string - alias domain name
     */
    public function addAliasDomainAsClient ($domain, $aliasDomain = null)
    {
        // If no alias domain provided, generate one based on domain
        if (!$aliasDomain)
            $aliasDomain = 'alias'.$domain;

        // Search for Aliases option and click it
        $this->searchAndClickCommandAsClient("Aliases");

        // Wait for Aliases page to load
        $this->waitForText('Create a New Alias');

        // Fill domain field
        $this->waitForElement(Locator::combine(CpanelClientPage::ALIAS_DOMAIN_FIELD_XPATH, CpanelClientPage::ALIAS_DOMAIN_FIELD_CSS), 10);
        $this->fillField(Locator::combine(CpanelClientPage::ALIAS_DOMAIN_FIELD_XPATH, CpanelClientPage::ALIAS_DOMAIN_FIELD_CSS), $aliasDomain);

        // Click Add Domain button
        $this->click('Add Domain');

        // Wait for command to finish
        $this->waitForText('You successfully created the alias, “'.$aliasDomain.'”.');

        return $aliasDomain;
    }

    /**
     * Function used to remove alias domain as client
     * @param $aliasDomain - alias domain to be removed
     */
    public function removeAliasDomainAsClient($aliasDomain)
    {
        // Search for Aliases option and click on it
        $this->searchAndClickCommandAsClient('Aliases');

        // Wait for Aliases page to load
        $this->waitForText('Create a New Alias');

        // Search for alias domain to be removed
        $this->fillField(Locator::combine(CpanelClientPage::ALIAS_DOMAIN_SEARCH_BAR_XPATH, CpanelClientPage::ALIAS_DOMAIN_SEARCH_BAR_CSS), $aliasDomain);

        // Click the search button
        $this->click(Locator::combine(CpanelClientPage::ALIAS_DOMAIN_SEARCH_BTN_XPATH, CpanelClientPage::ALIAS_DOMAIN_SEARCH_BTN_CSS));

        // Click the remove button
        $this->click("#del_");

        // Wait for removal confirmation
        $this->waitForText("Are you sure you want to permanently remove the alias, “".$aliasDomain."”?");

        // Click the remove button to confirm
        $this->click(Locator::combine(CpanelClientPage::DELETE_ALIAS_BTN_XPATH, CpanelClientPage::DELETE_ALIAS_BTN_XPATH));

        // Wait for confirmation message
        $this->waitForText("The alias, ".$aliasDomain.", has been successfully removed.", 30);

    }

    /**
     * Function used to login on spampanel
     * @param $domain - domain to log in with
     */
    public function loginOnSpampanel($domain)
    {
        $href = $this->grabAttributeFrom('//a[contains(text(), "Login")]', 'href');
        $this->amOnUrl($href);
        $this->waitForText("Welcome to the $domain control panel", 60);
        $this->see("Logged in as: $domain");
        $this->see("Domain User");
    }

    /**
     * Function used to add a route in spampanel
     * @param $route - route
     * @param int $port - port
     */
    public function addRouteInSpampanel($route, $port = 25)
    {
        // Got to edit route(s) page
        $this->waitForText('Edit route(s)', 10);
        $this->click('Edit route(s)');

        // Click add route button
        $this->click('Add a route');

        // Fill route host field
        $this->waitForElement(Locator::combine(SpampanelPage::ROUTE_FIELD_XPATH, SpampanelPage::ROUTE_FIELD_CSS));
        $this->fillField(Locator::combine(SpampanelPage::ROUTE_FIELD_XPATH, SpampanelPage::ROUTE_FIELD_CSS), $route);

        // Fill route port field
        $this->waitForElement(Locator::combine(SpampanelPage::PORT_FIELD_XPATH, SpampanelPage::PORT_FIELD_CSS));
        $this->fillField(Locator::combine(SpampanelPage::PORT_FIELD_XPATH, SpampanelPage::PORT_FIELD_CSS), $port);

        // Click the submit button
        $this->click('Save');

        // Check if route was succesfuly added
        $this->waitForText('Domain routes updated successfully');
    }

    /**
     * Function used to logout from spampanel
     */
    public function logoutFromSpampanel()
    {
        $this->waitForElementVisible(SpampanelPage::LOGOUT_LINK, 10);
        $this->click(SpampanelPage::LOGOUT_LINK);
        $this->waitForElementVisible(SpampanelPage::LOGOUT_CONFIRM_LINK, 10);
        $this->click(SpampanelPage::LOGOUT_CONFIRM_LINK);
    }

    /**
     * Function used to check if a domain exist in plugin domain list if logged in as client
     * @param $domain - domain to check
     */
    public function checkDomainListAsClient($domain)
    {
        // If $domain is an array $domains will be equal to $domain else $domains will become empty array
        $domains = is_array($domain) ? $domain : [];

        // Search plugin and click on it
        $this->searchAndClickCommandAsClient($this->currentBrandname);

        // Wait for plugin page to load
        $this->waitForText('List Domains');
        $this->see('This page shows you a list of all domains owned by you.');

        // Check that each domain in domains list is present in table
        foreach ($domains as $domain)
            $this->seeInDomainTable($domain);
    }

    /**
     * Function used to see if domain is present in plugin domains table
     * @param $domain - domain to be checked
     */
    public function seeInDomainTable($domain)
    {
        $this->see($domain, Locator::combine(DomainListPage::DOMAIN_TABLE_XPATH, DomainListPage::DOMAIN_TABLE_CSS));
    }

    /**
     * Function used to see if domain is not present in plugin domains table
     * @param $domain - domain to be checked
     */
    public function dontSeeInDomainTable($domain)
    {
        $this->dontSee($domain, Locator::combine(DomainListPage::DOMAIN_TABLE_XPATH, DomainListPage::DOMAIN_TABLE_CSS));
    }

    /**
     * Function used to remove all created accounts
     */
    public function removeCreatedAccounts()
    {
        $this->will("Remove created accounts");

        $usernames = array_map(function ($account) {return $account['username'];}, self::$accounts);

        if (!$usernames) {
            $this->comment("No created accounts to remove!");
            return;
        }

        $this->removeAccounts($usernames);
    }

    /**
     * Function used to remove an account
     * @param $username - username
     */
    public function removeAccount($username)
    {
        $this->removeAccounts(array($username));
    }

    /**
     * Function used to remove multiple accounts
     * @param array $usernames - array of usernames
     */
    public function removeAccounts(array $usernames)
    {
        $this->will("Terminate accounts: ".implode(', ', $usernames));

        // For each username
        foreach ($usernames as $username) {

            // Remove account from spampanel
            $this->makeCpanelApiRequest()->deleteAccount($username);
            $this->removeAccountByUsername($username);
        }
    }

    /**
     * Function used to remove all accounts from cPanel
     */
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

    public function seeMxEntriesInCpanelInterface($domain, array $mxRecords)
    {
        $existingMxRecords = $this->getMxEntriesFromCpanelInterface($domain);
        foreach ($mxRecords as $mxRecord) {
            $this->assertContains($mxRecord, $existingMxRecords);
        }
    }

    public function dontSeeMxEntriesInCpanelInterface($domain, array $mxRecords)
    {
        $existingMxRecords = $this->getMxEntriesFromCpanelInterface($domain);
        foreach ($mxRecords as $mxRecord) {
            $this->assertNotContains($mxRecord, $existingMxRecords);
        }
    }

    public function setDefaultConfigurationOptions()
    {
        $this->setConfigurationOptions($this->getDefaultConfigurationOptions());
    }

    /**
     * Function used to set plugin configuration options
     * @param array $options - array of options
     */
    public function setConfigurationOptions(array $options)
    {
        // Merge array with options with default configuration options array
        $options = array_merge($this->getDefaultConfigurationOptions(), $options);

        // For each option if array check or uncheck option if needed
        foreach ($options as $option => $check)
            if ($check)
                $this->checkOption($option);
            else
                $this->uncheckOption($option);

        // Click the save settings button
        $this->click("Save Settings");

        // Wait for settings to be saved
        $this->waitForText('The settings have been saved.', 60);
    }

    /**
     * Function used to go to configuration page and set given options
     *
     * @param array $options - array of options
     */
    public function goToConfigurationPageAndSetOptions(array $options)
    {
        $this->goToPage(ProfessionalSpamFilterPage::CONFIGURATION_BTN, ConfigurationPage::TITLE);
        $this->setConfigurationOptions($options);
    }

    /**
     * Function used to create default package in cPanel
     */
    public function createDefaultPackage()
    {
        // Search for delete package option and click on it
        $this->searchAndClickCommand('Delete a Package');

        // Check if the default package already exist
        $this->wait(2);
        $count = $this->getElementsCount("//select/option[@value='$this->defaultPackage']");

        if ($count) {
            $this->comment("Default package already created");
            return;
        }

        // Search for add a package option and click on it
        $this->searchAndClickCommand('Add a Package');

        // Switch to main frame
        $this->switchToMainFrame();

        // Fill the package name field
        $this->waitForElement(Locator::combine(CpanelWHMPage::PACKAGE_NAME_FIELD_XPATH, CpanelWHMPage::PACKAGE_NAME_FIELD_CSS), 10);
        $this->fillField(Locator::combine(CpanelWHMPage::PACKAGE_NAME_FIELD_XPATH, CpanelWHMPage::PACKAGE_NAME_FIELD_CSS), $this->defaultPackage);

        // Switch to main frame
        $this->switchToMainFrame();

        // Check unlimited parked domains
        $this->executeJS('document.getElementById("maxpark_unlimited_radio").setAttribute("checked", "true")');

        // Check unlimited addon domains
        $this->executeJS('document.getElementById("maxaddon_unlimited_radio").setAttribute("checked", "true")');

        // Click the save changes button
        $this->click('Add');

        // Wait for package creation to finish
        $this->waitForText("Success!");
    }

    /**
     * Function used to click the Home Menu Link on client page
     */
    public function clickHomeMenuLink()
    {
        // Wait for home menu link
        $this->waitForElement(Locator::combine(CpanelClientPage::HOME_MENU_LINK_XPATH, CpanelClientPage::HOME_MENU_LINK_CSS), 10);

        // Click the home menu link
        $this->click(Locator::combine(CpanelClientPage::HOME_MENU_LINK_XPATH, CpanelClientPage::HOME_MENU_LINK_CSS));
    }

    /**
     * Function used to access MX Entry menu as client in order to change email routing options
     */
    public function accessEmailRoutingInMxEntryPage()
    {
        // Search for MX Entry option and click on it
        $this->searchAndClickCommandAsClient("MX Entry");

        // Wait for page to load
        $this->waitForText("MX Entry");
        $this->waitForText("Email Routing");
    }

    /**
     * Function used to check if email routing is set to Local Mail Exchanger option
     */
    public function verifyEmailRoutingInMxEntryPageSetToLocal()
    {
        // Verify if Local Mail Exchanger option is checked
        $this->seeOptionIsSelected(".//*[@id='mxcheck_local']", "local");
    }

    /**
     * Function used to check if email routing is set to Backup Mail Exchanger option
     */
    public function verifyEmailRoutingInMxEntryPageSetToBackup()
    {
        // Verify if Backup Mail Exchanger option is checked
        $this->seeOptionIsSelected(".//*[@id='mxcheck_secondary']", "secondary");
    }

    /**
     * Function used to check if email routing is set to Remote Mail Exchanger
     */
    public function verifyEmailRoutingInMxEntryPageSetToRemote()
    {
        // Verify if Remote Email Exchanger is checked
        $this->seeOptionIsSelected(".//*[@id='mxcheck_remote']", "remote");
    }

    /**
     * Function used to change email routing to Local Mail Exchanger
     */
    public function changeEmailRoutingInMxEntryPageToLocalMailExchanger()
    {
        // Select the Local Mail Exchanger option
        $this->executeJS("document.getElementById('mxcheck_local').checked=true");

        // Click change button
        $this->executeJS("document.getElementById('change_mxcheck_button').click()");

        // Wait for settings to be saved
        $this->wait(15);
    }

    /**
     * Function used to change email routing to Backup Mail Exchanger option
     */
    public function changeEmailRoutingInMxEntryPageToBackupMailExchanger()
    {
        // Select Backup Mail Exchanger option
        $this->executeJS("document.getElementById('mxcheck_secondary').checked=true");

        // Click change button
        $this->executeJS("document.getElementById('change_mxcheck_button').click()");

        // Wait for settings to be saved
        $this->wait(15);
    }

    /**
     * Function used to change email routing to Remote Mail Exchanger option
     */
    public function changeEmailRoutingInMxEntryPageToRemoteMailExchanger()
    {
        // Select Remote Mail Exchanger option
        $this->executeJS("document.getElementById('mxcheck_remote').checked=true");

        // Click change button
        $this->executeJS("document.getElementById('change_mxcheck_button').click()");

        // Wait for settings to be saved
        $this->wait(15);
    }

    public function seeBulkProtectLastExecutionInfo()
    {
        $this->see('Bulk protect has been executed last at: ');
    }

    public function submitBulkprotectForm()
    {
        $this->click(Locator::combine(BulkprotectPage::EXECUTE_BULKPROTECT_BTN_XPATH, BulkprotectPage::EXECUTE_BULKPROTECT_BTN_CSS));
    }

    public function seeBulkprotectRanSuccessfully()
    {
        $this->waitForText("Bulkprotect", 200);
        $this->waitForElement(".//*[@id='bulkwarning']/div", 200);
        $this->waitForText("Bulkprotect has finished", 200);
        $this->see("The bulkprotect process has finished its work. Please see the tables below for the results.");
    }

    /**
     * Function used to get default configuration options for addon
     * @return array - array of default configuration options
     */
    private function getDefaultConfigurationOptions()
    {
        return array(
            Locator::combine(ConfigurationPage::ENABLE_SSL_FOR_API_OPT_XPATH, ConfigurationPage::ENABLE_SSL_FOR_API_OPT_CSS) => false,
            Locator::combine(ConfigurationPage::ENABLE_AUTOMATIC_UPDATES_OPT_XPATH, ConfigurationPage::ENABLE_AUTOMATIC_UPDATES_OPT_CSS) => true,
            Locator::combine(ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT_XPATH, ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT_CSS) => true,
            Locator::combine(ConfigurationPage::AUTOMATICALLY_DELETE_DOMAINS_OPT_XPATH, ConfigurationPage::AUTOMATICALLY_DELETE_DOMAINS_OPT_CSS) => true,
            Locator::combine(ConfigurationPage::AUTOMATICALLY_CHANGE_MX_OPT_XPATH, ConfigurationPage::AUTOMATICALLY_CHANGE_MX_OPT_CSS) => true,
            Locator::combine(ConfigurationPage::CONFIGURE_EMAIL_ADDRESS_OPT_XPATH, ConfigurationPage::CONFIGURE_EMAIL_ADDRESS_OPT_CSS) => true,
            Locator::combine(ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_XPATH, ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_CSS) => true,
            Locator::combine(ConfigurationPage::ADD_ADDON_AS_ALIAS_CPANEL_OPT_XPATH, ConfigurationPage::ADD_ADDON_AS_ALIAS_CPANEL_OPT_CSS) => false,
            Locator::combine(ConfigurationPage::USE_EXISTING_MX_OPT_XPATH, ConfigurationPage::USE_EXISTING_MX_OPT_CSS) => true,
            Locator::combine(ConfigurationPage::DO_NOT_PROTECT_REMOTE_DOMAINS_OPT_XPATH, ConfigurationPage::DO_NOT_PROTECT_REMOTE_DOMAINS_OPT_CSS) => true,
            Locator::combine(ConfigurationPage::REDIRECT_BACK_TO_CPANEL_OPT_XPATH, ConfigurationPage::REDIRECT_BACK_TO_CPANEL_OPT_CSS) => false,
            Locator::combine(ConfigurationPage::ADD_DOMAIN_DURING_LOGIN_OPT_XPATH, ConfigurationPage::ADD_DOMAIN_DURING_LOGIN_OPT_CSS) => true,
            Locator::combine(ConfigurationPage::FORCE_CHANGE_MX_ROUTE_OPT_XPATH, ConfigurationPage::FORCE_CHANGE_MX_ROUTE_OPT_CSS) => false,
            Locator::combine(ConfigurationPage::CHANGE_EMAIL_ROUTING_OPT_XPATH, ConfigurationPage::CHANGE_EMAIL_ROUTING_OPT_CSS) => true,
            Locator::combine(ConfigurationPage::ADD_REMOVE_DOMAIN_XPATH, ConfigurationPage::ADD_REMOVE_DOMAIN_CSS) => false,
            Locator::combine(ConfigurationPage::DISABLE_ADDON_IN_CPANEL_XPATH, ConfigurationPage::DISABLE_ADDON_IN_CPANEL_CSS) => false,
            Locator::combine(ConfigurationPage::USE_IP_AS_DESTINATION_OPT_XPATH, ConfigurationPage::USE_IP_AS_DESTINATION_OPT_CSS) => false,
            Locator::combine(ConfigurationPage::SET_SPF_RECORD_XPATH, ConfigurationPage::SET_SPF_RECORD_CSS) => false,
        );
    }

    /**
     * Function used to get client login url
     * @return string - client url
     */
    private function getClientUrl()
    {
        // Get the default cPanel url
        $url = getenv($this->getEnvParameter('url'));

        // Replace the port in order to obtain client url
        return str_replace('2087', '2083', $url);
    }

    /**
     * Function used to remove an account from accounts array
     * @param $username - username to be removed
     */
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