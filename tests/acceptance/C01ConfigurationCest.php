<?php

use Page\DomainListPage;
use Page\ConfigurationPage;
use Page\ProfessionalSpamFilterPage;
use Step\Acceptance\ConfigurationSteps;
use Codeception\Util\Locator;

class C01ConfigurationCest
{
    /**
     * Function called before each test
     */
    public function _before(ConfigurationSteps $I)
    {
        $I->loginAsRoot();
        $I->createDefaultPackage();
        $I->goToPage(ProfessionalSpamFilterPage::CONFIGURATION_BTN, ConfigurationPage::TITLE);
    }

    /**
     * Function called after each test
     */
    public function _after(ConfigurationSteps $I)
    {
        $I->removeCreatedAccounts();
    }

    /**
     * Function called after a test failed
     */
    public function _failed(ConfigurationSteps $I)
    {
        $this->_after($I);
    }

    /**
     * Verify the 'Configuration page' layout and functionality
     */
    public function checkConfigurationPage(ConfigurationSteps $I)
    {
        // Verify configuration page layout
        $I->verifyPageLayout();

        // Fill configuration fields
        $I->setFieldApiUrl(PsfConfig::getApiUrl());
        $I->setFieldApiHostname(PsfConfig::getApiHostname());
        $I->setFieldApiUsernameIfEmpty(PsfConfig::getApiUsername());
        $I->setFieldApiPassword(PsfConfig::getApiPassword());
        $I->setFieldPrimaryMX(PsfConfig::getPrimaryMX());

        // Submit settings
        $I->submitSettingForm();

        // Check if configuration was saved
        $I->seeSubmissionIsSuccessful();
    }

    /**
     * Verify "Automatically add domains to the Spamfilter" option works properly when unchecked
     */
    public function verifyNotAutomaticallyAddDomainToPsf(ConfigurationSteps $I)
    {
        // Set plugin configuration options
        $I->setConfigurationOptions(array(
            Locator::combine(ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT_XPATH, ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT_CSS) => false
        ));

        // Create new client account
        $account = $I->createNewAccount();

        // Cgeck if domain is not present in filter
        $I->checkDomainIsNotPresentInFilter($account['domain']);
    }

    /**
     * Verify "Automatically add domains to the Spamfilter" option works properly when checked
     */
    public function verifyAutomaticallyAddDomainToPsf(ConfigurationSteps $I)
    {
        // Set plugin configuration options
        $I->setConfigurationOptions(array(
            Locator::combine(ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT_XPATH, ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT_CSS) => true,
            Locator::combine(ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_XPATH, ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_CSS) => true,
            Locator::combine(ConfigurationPage::DO_NOT_PROTECT_REMOTE_DOMAINS_OPT_XPATH, ConfigurationPage::DO_NOT_PROTECT_REMOTE_DOMAINS_OPT_CSS) => false,
        ));

        // Create new client account
        $account = $I->createNewAccount();

        // Login with the client account
        $I->loginAsClient($account['username'], $account['password']);

        // Add addon domain as client
        $addonDomainName = $I->addAddonDomainAsClient($account['domain']);

        // Add alias domain as client
        $aliasDomainName = $I->addAliasDomainAsClient($account['domain']);

        // Add sub domain as client
        $subDomain = $I->addSubdomainAsClient($account['domain']);

        // Check if previous created domains exist in spampanel
        $I->assertDomainExistsInSpampanel($account['domain']);
        $I->assertDomainExistsInSpampanel($addonDomainName);
        $I->assertDomainExistsInSpampanel($aliasDomainName);
        $I->assertDomainExistsInSpampanel($subDomain);

        // Login back as root
        $I->loginAsRoot();

        // Check if domain is present in filter and is protected
        $I->checkDomainIsPresentInFilter($account['domain']);
    }

    /**
     * Verify "Automatically delete domains from the SpamFilter" option works properly when unchecked
     */
    public function verifyNotAutomaticallyDeleteDomainToPsf(ConfigurationSteps $I)
    {
        // Set plugin configuration options
        $I->setConfigurationOptions(array(
            Locator::combine(ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT_XPATH, ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT_CSS) => true,
            Locator::combine(ConfigurationPage::AUTOMATICALLY_DELETE_DOMAINS_OPT_XPATH, ConfigurationPage::AUTOMATICALLY_DELETE_DOMAINS_OPT_CSS) => false

        ));

        // Create a new client account
        $account = $I->createNewAccount();

        // Check if domain exists in spampanel
        $domainExists = $I->makeSpampanelApiRequest()->domainExists($account['domain']);
        $I->assertTrue($domainExists);

        // Remove the client account
        $I->removeAccount($account['username']);

        // Check if domain was removed from spampanel
        $domainExists = $I->makeSpampanelApiRequest()->domainExists($account['domain']);
        $I->assertTrue($domainExists);
    }

    /**
     * Verify if client account is removed all the domains created by him will be removed from spampanel
     */
    public function verifyAutomaticallyDeleteDomainToPsf(ConfigurationSteps $I)
    {
        // Set plugin configuration options
        $I->setConfigurationOptions(array(
            Locator::combine(ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT_XPATH, ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT_CSS) => true,
            Locator::combine(ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_XPATH, ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_CSS) => true,
            Locator::combine(ConfigurationPage::DO_NOT_PROTECT_REMOTE_DOMAINS_OPT_XPATH, ConfigurationPage::DO_NOT_PROTECT_REMOTE_DOMAINS_OPT_CSS) => false,
            Locator::combine(ConfigurationPage::AUTOMATICALLY_DELETE_DOMAINS_OPT_XPATH, ConfigurationPage::AUTOMATICALLY_DELETE_DOMAINS_OPT_CSS) => true

        ));

        // Create a new client account
        $account = $I->createNewAccount();

        // Login with the client account
        $I->loginAsClient($account['username'], $account['password']);

        // Create addon domain as client
        $addonDomainName = $I->addAddonDomainAsClient($account['domain']);

        // Create alias domain as client
        $aliasDomain = $I->addAliasDomainAsClient($account['domain']);

        // Create sub domain as client
        $subDomain = $I->addSubdomainAsClient($account['domain']);

        // Check if previous created domains exist in spampanel
        $I->assertDomainExistsInSpampanel($account['domain']);
        $I->assertDomainExistsInSpampanel($addonDomainName);
        $I->assertDomainExistsInSpampanel($aliasDomain);
        $I->assertDomainExistsInSpampanel($subDomain);

        // Remove the client account
        $I->removeAccount($account['username']);

        // Check if the previous created  domains were removed from spampanel
        $I->assertDomainNotExistsInSpampanel($account['domain']);
        $I->assertDomainNotExistsInSpampanel($addonDomainName);
        $I->assertDomainNotExistsInSpampanel($aliasDomain);
        $I->assertDomainNotExistsInSpampanel($subDomain);
    }

    /**
     * Verify if domains removed by client are removed from spampanel
     */
    public function verifyAutmaticallyDeleteSecondaryDomains(ConfigurationSteps $I)
    {
        // Set plugin configuration options
        $I->setConfigurationOptions(array(
            Locator::combine(ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT_XPATH, ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT_CSS) => true,
            Locator::combine(ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_XPATH, ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_CSS) => true,
            Locator::combine(ConfigurationPage::DO_NOT_PROTECT_REMOTE_DOMAINS_OPT_XPATH, ConfigurationPage::DO_NOT_PROTECT_REMOTE_DOMAINS_OPT_CSS) => false,
            Locator::combine(ConfigurationPage::AUTOMATICALLY_DELETE_DOMAINS_OPT_XPATH,ConfigurationPage::AUTOMATICALLY_DELETE_DOMAINS_OPT_CSS) => true

        ));

        // Create a new client account
        $account = $I->createNewAccount();

        // Login with the client account
        $I->loginAsClient($account['username'], $account['password']);

        // Create addon domain as client
        $addonDomainName = $I->addAddonDomainAsClient($account['domain']);

        // Create alias domain as client
        $aliasDomain = $I->addAliasDomainAsClient($account['domain']);

        // Create a subdomain as client
        $subDomain = $I->addSubdomainAsClient($account['domain']);

        // Check if previous created domains exist in spampanel
        $I->assertDomainExistsInSpampanel($account['domain']);
        $I->assertDomainExistsInSpampanel($addonDomainName);
        $I->assertDomainExistsInSpampanel($aliasDomain);
        $I->assertDomainExistsInSpampanel($subDomain);

        // Remove the addon domain as client
        $I->removeAddonDomainAsClient($addonDomainName);

        // Check if the addon domain was removed from spampanel
        $I->assertDomainNotExistsInSpampanel($addonDomainName);

        // Remove the sub domain as client
        $I->removeSubdomainAsClient($subDomain);

        // Check if the sub domain was removed from spampanel
        $I->assertDomainNotExistsInSpampanel($subDomain);

        // Remove alias domain as client
        $I->removeAliasDomainAsClient($aliasDomain);

        // Check if the alias domain was removed from spampanel
        $I->assertDomainNotExistsInSpampanel($aliasDomain);
    }

    /**
     * Verify "Automatically change the MX records for domains" option works properly when unchecked
     */
    public function verifyNotAutomaticallyChangeMxRecords(ConfigurationSteps $I)
    {
        // Set plugin configuration options
        $I->setConfigurationOptions(array(
            Locator::combine(ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT_XPATH, ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT_CSS) => true,
            Locator::combine(ConfigurationPage::AUTOMATICALLY_CHANGE_MX_OPT_XPATH, ConfigurationPage::AUTOMATICALLY_CHANGE_MX_OPT_CSS) => false
        ));

        // Create new client account
        $account = $I->createNewAccount();

        // Click on Edit MX Entry command
        $I->searchAndClickCommand('Edit MX Entry');

        // Select the previous created account from list
        $I->selectOption('domainselect', $account['domain']);

        // Click the Edit button
        $I->click('Edit');

        // Check if primary MX was not set
        $I->dontSeeInField('#mxlisttb input', PsfConfig::getPrimaryMX());
    }

    /**
     * Verify "Automatically change the MX records for domains" option works properly when checked
     */
    public function verifyAutomaticallyChangeMxRecords(ConfigurationSteps $I)
    {
        // Set plugin configuration options
        $I->setConfigurationOptions(array(
            Locator::combine(ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT_XPATH, ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT_CSS) => true,
            Locator::combine(ConfigurationPage::AUTOMATICALLY_CHANGE_MX_OPT_XPATH, ConfigurationPage::AUTOMATICALLY_CHANGE_MX_OPT_CSS) => true
        ));

        // Create new client account
        $account = $I->createNewAccount();

        // Click on Edit Mx Entry command
        $I->searchAndClickCommand('Edit MX Entry');

        // Select the previous created account from list
        $I->selectOption('domainselect', $account['domain']);

        // Click the Edit button
        $I->click('Edit');

        // Check if the primary MX was set
        $I->seeInField('#mxlisttb input', PsfConfig::getPrimaryMX());
    }


    /**
     * Verify hook email routing at client level
     */
    public function verifyHookSetEmailRoutingAtClientLevel(ConfigurationSteps $I)
    {
        // Set plugin configuration options
        $I->setConfigurationOptions(array(
            Locator::combine(ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT_XPATH, ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT_CSS) => true,
            Locator::combine(ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_XPATH, ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_CSS) => true,
            Locator::combine(ConfigurationPage::DO_NOT_PROTECT_REMOTE_DOMAINS_OPT_XPATH, ConfigurationPage::DO_NOT_PROTECT_REMOTE_DOMAINS_OPT_CSS) => false,
            Locator::combine(ConfigurationPage::AUTOMATICALLY_DELETE_DOMAINS_OPT_XPATH, ConfigurationPage::AUTOMATICALLY_DELETE_DOMAINS_OPT_CSS) => true,
            Locator::combine(ConfigurationPage::ADD_REMOVE_DOMAIN_XPATH, ConfigurationPage::ADD_REMOVE_DOMAIN_CSS) => true
        ));

        // Get MX fields from addon configuration page
        $spampanelMxRecords = $I->getMxFields();

        // Create a new client account
        $account = $I->createNewAccount();

        // Login as client
        $I->loginAsClient($account['username'], $account['password']);

        // Get destination routes from spampanel
        $destinationRoutes = $I->makeSpampanelApiRequest()->getDomainRoutesNames($account['domain']);

        // Go to MX Entry page in order to access Email Routing options
        $I->accessEmailRoutingInMxEntryPage();

        // Change email routing option to Backup Mail Exchanger
        $I->changeEmailRoutingInMxEntryPageToBackupMailExchanger();

        // Logout from client account
        $I->logoutAsClient();

        // Login as root
        $I->loginAsRoot();

        // Check if domain don't exist in spampanel
        $I->assertDomainNotExistsInSpampanel($account['domain']);

        // Check if spampanel destination routes exist in cPanel interface
        $I->seeMxEntriesInCpanelInterface($account['domain'], $destinationRoutes);

        // Check if MX entries from addon configuration page don't exist in cPanel interface
        $I->dontSeeMxEntriesInCpanelInterface($account['domain'], $spampanelMxRecords);

        // Login as client
        $I->loginAsClient($account['username'], $account['password']);

        // Go to MX Entry page in order to access Email Routing options
        $I->accessEmailRoutingInMxEntryPage();

        // Change email routing option to Local Mail Exchanger
        $I->changeEmailRoutingInMxEntryPageToLocalMailExchanger();

        // Logout from client account
        $I->logoutAsClient();

        // Login as root
        $I->loginAsRoot();

        // Check if domain exist in domain list
        $I->searchDomainList($account['domain']);

        // Check if domain exist in spampanel
        $I->assertDomainExistsInSpampanel($account['domain']);

        // Check if MX entries from addon configuration page exist in cPanel interface
        $I->seeMxEntriesInCpanelInterface($account['domain'], $spampanelMxRecords);

        // Login as client
        $I->loginAsClient($account['username'], $account['password']);

        // Get destination routes from spampanel
        $destinationRoutes = $I->makeSpampanelApiRequest()->getDomainRoutesNames($account['domain']);

        // Go to MX Entry page in order to access Email Routing options
        $I->accessEmailRoutingInMxEntryPage();

        // Change email routing option to Remote Mail Exchanger
        $I->changeEmailRoutingInMxEntryPageToRemoteMailExchanger();

        // Logout from client account
        $I->logoutAsClient();

        // Login as root
        $I->loginAsRoot();

        // Check if domain exist in domain list
        $I->searchDomainList($account['domain']);

        // Check if domain don;t exist in spampanel
        $I->assertDomainNotExistsInSpampanel($account['domain']);

        // Check if spampanel destination routes exist in cPanel interface
        $I->seeMxEntriesInCpanelInterface($account['domain'], $destinationRoutes);

        // Check if MX entries from addon configuration page don't exist in cPanel interface
        $I->dontSeeMxEntriesInCpanelInterface($account['domain'], $spampanelMxRecords);
    }

    /**
     * Verify hook email routing when using bulkprotect
     */
    public function verifyHookSetEmailRoutingWhileUsingBulkprotect(ConfigurationSteps $I)
    {
        // Set plugin configuration options
        $I->setConfigurationOptions(array(
            Locator::combine(ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT_XPATH, ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT_CSS) => false,
            Locator::combine(ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_XPATH, ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_CSS) => true,
            Locator::combine(ConfigurationPage::DO_NOT_PROTECT_REMOTE_DOMAINS_OPT_XPATH, ConfigurationPage::DO_NOT_PROTECT_REMOTE_DOMAINS_OPT_CSS) => false,
            Locator::combine(ConfigurationPage::AUTOMATICALLY_DELETE_DOMAINS_OPT_XPATH, ConfigurationPage::AUTOMATICALLY_DELETE_DOMAINS_OPT_CSS) => true,
            Locator::combine(ConfigurationPage::ADD_REMOVE_DOMAIN_XPATH, ConfigurationPage::ADD_REMOVE_DOMAIN_CSS) => true
        ));


        # Change email routing to other than Local

        // Remove all previous created accounts
        $I->removeAllAccounts();

        // Create new client account
        $account = $I->createNewAccount();

        // Login as client
        $I->loginAsClient($account['username'], $account['password']);

        // Go to MX Entry page in order to access Email Routing options
        $I->accessEmailRoutingInMxEntryPage();

        // Check that Local Mail Exchanger option is selected
        $I->verifyEmailRoutingInMxEntryPageSetToLocal();

        // Change email routing option to Backup Mail Exchanger
        $I->changeEmailRoutingInMxEntryPageToBackupMailExchanger();

        // Check email routing option is set to Backup Mail Exchanger
        $I->verifyEmailRoutingInMxEntryPageSetToBackup();

        // Logout from client account
        $I->logoutAsClient();

        // Login as root
        $I->loginAsRoot();

        // Check if domain exist in domain list
        $I->searchDomainList($account['domain']);


        #  Email routing should not change when domain is protected

        // Toggle protection for that domain
        $I->click(DomainListPage::TOGGLE_PROTECTION_LINK);

        // Wait for protection status to change
        $I->waitForText("The protection status of {$account['domain']} has been changed to protected", 60);

        // Logout from root account
        $I->logout();

        // Login as client
        $I->loginAsClient($account['username'], $account['password']);

        // Go to MX Entry page in order to access Email Routing options
        $I->accessEmailRoutingInMxEntryPage();

        // Check that Local Mail Exchanger option is selected
        $I->verifyEmailRoutingInMxEntryPageSetToLocal();

        // Logout from client account
        $I->logoutAsClient();

        // Login as root
        $I->loginAsRoot();

        // Check if domain exist in domain list
        $I->searchDomainList($account['domain']);


        #Email routing should not change when domain is unprotected

        // Toggle protection for that domain
        $I->click(DomainListPage::TOGGLE_PROTECTION_LINK);

        // Wait for protection status to change
        $I->waitForText("The protection status of {$account['domain']} has been changed to unprotected", 60);

        // Login as client
        $I->loginAsClient($account['username'], $account['password']);

        // Go to MX Entry page in order to access Email Routing options
        $I->accessEmailRoutingInMxEntryPage();

        // Check that Local Mail Exchanger option is selected
        $I->verifyEmailRoutingInMxEntryPageSetToLocal();

        // Logout from client account
        $I->logoutAsClient();

        // Login as root
        $I->loginAsRoot();

        // Check if domain exist in domain list
        $I->searchDomainList($account['domain']);

        # Email routing should not change when domain is protected again

        // Toggle protection for that domain
        $I->click(DomainListPage::TOGGLE_PROTECTION_LINK);

        // Wait for protection status to change
        $I->waitForText("The protection status of {$account['domain']} has been changed to protected", 60);

        // Login as client
        $I->loginAsClient($account['username'], $account['password']);

        // Go to MX Entry page in order to access Email Routing options
        $I->accessEmailRoutingInMxEntryPage();

        // Change email routing option to Remote Mail Exchanger
        $I->changeEmailRoutingInMxEntryPageToRemoteMailExchanger();

        // Check that Remote Email Exchanger option is selected
        $I->verifyEmailRoutingInMxEntryPageSetToRemote();

        // Logout from client account
        $I->logoutAsClient();

        // Login as root
        $I->loginAsRoot();

        // Check if domain exist in domain list
        $I->searchDomainList($account['domain']);

        // Check if protection status for that domain is Unprotected
        $I->checkProtectionStatusIs(DomainListPage::STATUS_DOMAIN_IS_NOT_PRESENT_IN_THE_FILTER);

        // Go to bulk protect page
        $I->goToPage(\Page\ProfessionalSpamFilterPage::BULKPROTECT_BTN, \Page\BulkprotectPage::TITLE);

        // Check bulk protect last execution info
        $I->seeBulkProtectLastExecutionInfo();

        // Run bulk protect action
        $I->submitBulkprotectForm();

        // Check if bulk protect finished succesfuly
        $I->seeBulkprotectRanSuccessfully();

        // Check if domain has been added in bulk protect table
        $I->see('Domain has been added', \Page\BulkprotectPage::TABLE);

        // Logout from root account
        $I->logout();

        // Login as client
        $I->loginAsClient($account['username'], $account['password']);

        // Go to MX Entry page in order to access Email Routing options
        $I->accessEmailRoutingInMxEntryPage();

        // Check that Local Email Exchanger option is selected
        $I->verifyEmailRoutingInMxEntryPageSetToLocal();
    }

    /**
     * Verify "Configure the email address for this domain" option works properly when unchecked
     */
    public function verifyNotConfigureTheEmailAddressForThisDomainOption(ConfigurationSteps $I)
    {
        // Set plugin configuration options
        $I->setConfigurationOptions(array(
            Locator::combine(ConfigurationPage::CONFIGURE_EMAIL_ADDRESS_OPT_XPATH, ConfigurationPage::CONFIGURE_EMAIL_ADDRESS_OPT_CSS) => false
        ));

        // Create new client account
        $account = $I->createNewAccount();

        // Search account in the domain list
        $I->searchDomainList($account['domain']);

        // Login on spampanel
        $I->loginOnSpampanel($account['domain']);

        // Go to domain settings page
        $I->click('Domain settings');

        // Check if previous created account email is not set
        $I->dontSeeInField('#contact_email', $account['email']);
    }

    /**
     * Verify "Configure the email address for this domain" option works properly when checked
     */
    public function verifyConfigureTheEmailAddressForThisDomainOption(ConfigurationSteps $I)
    {
        // Set plugin configuration options
        $I->setConfigurationOptions(array(
            Locator::combine(ConfigurationPage::CONFIGURE_EMAIL_ADDRESS_OPT_XPATH, ConfigurationPage::CONFIGURE_EMAIL_ADDRESS_OPT_CSS) => true
        ));

        // Create new client account
        $account = $I->createNewAccount();

        // Search account in the domain list
        $I->searchDomainList($account['domain']);

        // Login on spampanel
        $I->loginOnSpampanel($account['domain']);

        // Go to domain settings page
        $I->click('Domain settings');

        // Check if previous created account email is set
        $I->seeInField('#contact_email', $account['email']);
    }

    /**
     * Verify 'Use existing MX records as routes in the spamfilter' option works properly when unchecked
     */
    public function verifyUseExistingMXRecordsAsRoutesInTheSpamfilterOption(ConfigurationSteps $I)
    {
        // Set plugin configuration options
        $I->setConfigurationOptions(array(
            Locator::combine(ConfigurationPage::USE_EXISTING_MX_OPT_XPATH, ConfigurationPage::USE_EXISTING_MX_OPT_CSS) => true
        ));

        // Create new client account
        $account = $I->createNewAccount();

        // Get new account domain routes from spampanel
        $routes = $I->makeSpampanelApiRequest()->getDomainRoutes($account['domain']);

        // Assert that new account domain exist in routes
        $I->assertContains($account['domain'].'::25', $routes);
    }

    /**
     * Verify 'Use existing MX records as routes in the spamfilter' option works properly when checked
     */
    public function verifyNotUseExistingMXRecordsAsRoutesInTheSpamfilterOption(ConfigurationSteps $I)
    {
        // Set plugin configuration options
        $I->setConfigurationOptions(array(
            Locator::combine(ConfigurationPage::USE_EXISTING_MX_OPT_XPATH, ConfigurationPage::USE_EXISTING_MX_OPT_CSS) => false
        ));

        // Create new client account
        $account = $I->createNewAccount();

        // Get new account domain routes from spampanel
        $routes = $I->makeSpampanelApiRequest()->getDomainRoutes($account['domain']);

        // Assert that the hostname exist in routes
        $I->assertContains($I->getEnvHostname().'::25', $routes);
    }

    /**
     * Verify 'Use IP as destination route instead of domain' option works properly when checked
     */
    public function verifyUseIPAsDestinationRouteInsteadOfDomainOption(ConfigurationSteps $I)
    {
        // Set plugin configuration options
        $I->setConfigurationOptions(array(
            Locator::combine(ConfigurationPage::USE_EXISTING_MX_OPT_XPATH, ConfigurationPage::USE_EXISTING_MX_OPT_CSS) => false,
            Locator::combine(ConfigurationPage::USE_IP_AS_DESTINATION_OPT_XPATH, ConfigurationPage::USE_IP_AS_DESTINATION_OPT_CSS) => true
        ));

        // Create new client account
        $account = $I->createNewAccount();

        // Get hostname ip
        $ip = gethostbyname($I->getEnvHostname());

        // Get new account domain routes from spampanel
        $routes = $I->makeSpampanelApiRequest()->getDomainRoutes($account['domain']);

        // Assert that the hostname ip exist in routes
        $I->assertContains($ip.'::25', $routes);
    }

    /**
     * Verify 'Use IP as destination route instead of domain' option works properly when unchecked
     */
    public function verifyNotUseIPAsDestinationRouteInsteadOfDomainOption(ConfigurationSteps $I)
    {
        // Set plugin configuration options
        $I->setConfigurationOptions(array(
            Locator::combine(ConfigurationPage::USE_IP_AS_DESTINATION_OPT_XPATH, ConfigurationPage::USE_IP_AS_DESTINATION_OPT_CSS) => false,
            Locator::combine(ConfigurationPage::USE_EXISTING_MX_OPT_XPATH, ConfigurationPage::USE_EXISTING_MX_OPT_CSS) => true,
        ));

        // Create new client account
        $account = $I->createNewAccount();

        // Get new account domain routes from spampanel
        $routes = $I->makeSpampanelApiRequest()->getDomainRoutes($account['domain']);

        // Assert that the new account domain exist in routes
        $I->assertContains($account['domain'].'::25', $routes);
    }

    /**
     * Verify 'Process addon-, parked and subdomains' option works properly for addon domains when checked
     */
    public function verifyAddonDomains(ConfigurationSteps $I)
    {
        // Set plugin configuration options
        $I->setConfigurationOptions(array(
            Locator::combine(ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_XPATH, ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_CSS) => true
        ));

        // Create new client account
        $account = $I->createNewAccount();

        // Login as client
        $I->loginAsClient($account['username'], $account['password']);

        // Create new addon domain as client
        $addonDomainName = $I->addAddonDomainAsClient($account['domain']);

        // Check if alias domain exist in plugin domains list
        $I->checkDomainListAsClient($addonDomainName);

        // Logout from client account
        $I->logoutAsClient();

        // Login as root
        $I->loginAsRoot();

        // Search the addon domain in the domain list
        $I->searchDomainList($addonDomainName);
        $I->see('addon', DomainListPage::TYPE_COLUMN_FROM_FIRST_ROW);

        // Check if domain exist in spampanel
        $I->assertDomainExistsInSpampanel($addonDomainName);
    }

    /**
     * Verify 'Process addon-, parked and subdomains' option works properly for subdomains when checked
     */
    public function verifySubDomains(ConfigurationSteps $I)
    {
        // Set plugin configuration options
        $I->setConfigurationOptions(array(
            Locator::combine(ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_XPATH, ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_CSS) => true
        ));

        // Create new client account
        $account = $I->createNewAccount();

        // Login as client
        $I->loginAsClient($account['username'], $account['password']);

        // Create new subdomain as client
        $subDomain = $I->addSubdomainAsClient($account['domain']);

        // Check if sub domain exist in plugin domains list
        $I->checkDomainListAsClient($subDomain);

        // Logout from client account
        $I->logoutAsClient();

        // Login as root
        $I->loginAsRoot();

        // Search the subdomain in the domain list
        $I->searchDomainList($subDomain);
        $I->see('subdomain', DomainListPage::TYPE_COLUMN_FROM_FIRST_ROW);

        // Check if subdomain exist in spampanel
        $I->assertDomainExistsInSpampanel($subDomain);
    }

    /**
     * Verify 'Process addon-, parked and subdomains' option works properly for parked domains when checked
     */
    public function verifyAliasDomains(ConfigurationSteps $I)
    {
        // Set plugin configuration options
        $I->setConfigurationOptions(array(
            Locator::combine(ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_XPATH, ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_CSS) => true,
            Locator::combine(ConfigurationPage::DO_NOT_PROTECT_REMOTE_DOMAINS_OPT_XPATH, ConfigurationPage::DO_NOT_PROTECT_REMOTE_DOMAINS_OPT_CSS) => false
        ));

        // Create new client account
        $account = $I->createNewAccount();

        // Login as client
        $I->loginAsClient($account['username'], $account['password']);

        // Creat a new alias domain as client
        $aliasDomain= $I->addAliasDomainAsClient($account['domain']);

        // Check if alias domain exist in plugin domains list
        $I->checkDomainListAsClient($aliasDomain);

        // Logout from client account
        $I->logoutAsClient();

        // Login as root
        $I->loginAsRoot();

        // Search the alias domain in the domain list
        $I->searchDomainList($aliasDomain);
        $I->see('parked', DomainListPage::TYPE_COLUMN_FROM_FIRST_ROW);

        // Check if alias domain exist in spampanel
        $I->assertDomainExistsInSpampanel($aliasDomain);
    }

    /**
     * Verify 'Redirect back to Cpanel upon logout' option works properly when checked
     */
    public function verifyRedirectBackToCpanelUponLogout(ConfigurationSteps $I)
    {
        // Set plugin configuration options
        $I->setConfigurationOptions(array(
            Locator::combine(ConfigurationPage::REDIRECT_BACK_TO_CPANEL_OPT_XPATH, ConfigurationPage::REDIRECT_BACK_TO_CPANEL_OPT_CSS) => true
        ));

        // Create a new client account
        $account = $I->createNewAccount();

        // Login as client
        $I->loginAsClient($account['username'], $account['password']);

        // Check plugin domain list as client
        $I->checkDomainListAsClient($account['domain']);

        // Login on spampanel
        $I->loginOnSpampanel($account['domain']);

        // Logout from spampanel
        $I->logoutFromSpampanel();

        // See if cPanel client page redirection works when logout from spampanel
        $I->seeInCurrentAbsoluteUrl($I->getEnvHostname());

        // Logout as client
        $I->logoutAsClient();

        // Login as root
        $I->loginAsRoot();

        // Search the account domain in the domain list
        $I->searchDomainList($account['domain']);

        // Login on spampanel
        $I->loginOnSpampanel($account['domain']);

        // Logout from spampanel
        $I->logoutFromSpampanel();

        // See if cPanel admin page redirection works when logout from spampanel
        $I->seeInCurrentAbsoluteUrl($I->getEnvHostname());
    }

    /**
     * Verify 'Add addon-, parked and subdomains as an alias instead of a normal domain' option works properly when checked
     */
    public function verifyAddAddonParkedAndSubdomainsAsAnAliasInsteadOfANormalDomain(ConfigurationSteps $I)
    {
        // Set plugin configuration options
        $I->setConfigurationOptions(array(
            Locator::combine(ConfigurationPage::ADD_ADDON_AS_ALIAS_CPANEL_OPT_XPATH, ConfigurationPage::ADD_ADDON_AS_ALIAS_CPANEL_OPT_CSS) => true,
            Locator::combine(ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_XPATH, ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_CSS) => true,
            Locator::combine(ConfigurationPage::DO_NOT_PROTECT_REMOTE_DOMAINS_OPT_XPATH, ConfigurationPage::DO_NOT_PROTECT_REMOTE_DOMAINS_OPT_CSS) => false
        ));

        // Create new client account
        $account = $I->createNewAccount();

        // Login as client
        $I->loginAsClient($account['username'], $account['password']);

        // Create new addon domain as client
        $addonDomainName = $I->addAddonDomainAsClient($account['domain']);

        // Check if addon domain exist as an alias in spampanel
        $I->assertIsAliasInSpampanel($addonDomainName, $account['domain']);

        // Create new alias domain as client
        $parkedDomain = $I->addAliasDomainAsClient($account['domain']);

        // Check if alias domain exist as an alias in spampanel
        $I->assertIsAliasInSpampanel($parkedDomain, $account['domain']);

        // Create new subdomain as client
        $subDomain = $I->addSubdomainAsClient($account['domain']);

        // Check if subdomain exist as an alias in spampanel
        $I->assertIsAliasInSpampanel($subDomain, $account['domain']);
    }

    /**
     * Verify mx records are restored properly in cpanel when a domain is unprotected
     */
    public function verifyAllRoutesFromSpampanelAreAddedInCpanelOnDomainUnprotect(ConfigurationSteps $I)
    {
        // Set plugin configuration options
        $I->setConfigurationOptions(array(
            Locator::combine(ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT_XPATH, ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT_CSS) => true
        ));

        // All routes are the same as route domain
        $routeDomain = 'server9.seinternal.com';
        $routes = [$routeDomain, '5.79.73.204', '2001:1af8:4700:a02d:2::1'];

        foreach ($routes as $route) {

            $account = $I->createNewAccount();

            $I->searchDomainList($account['domain']);
            $I->loginOnSpampanel($account['domain']);
            $I->addRouteInSpampanel($route);

            $I->loginAsRoot();
            $I->searchDomainList($account['domain']);
            $I->click(DomainListPage::TOGGLE_PROTECTION_LINK);
            $I->waitForText("The protection status of {$account['domain']} has been changed to unprotected", 60);

            $values = $I->getMxEntriesFromCpanelInterface($account['domain']);
            $I->assertContains($routeDomain, $values);
        }
    }

    /**
     * Verify 'Set SPF automatically for domains' option works properly when checked
     */
    public function verifySetSpfRecord(ConfigurationSteps $I)
    {
        // Spf record value
        $spfRecord = 'v=spf1 a:' . PsfConfig::getApiHostname();

        // Fill the spf record field with the value previous generated
        $I->setFieldSpfRecord($spfRecord);

        // Set plugin configuration options
        $I->setConfigurationOptions(array(
            Locator::combine(ConfigurationPage::SET_SPF_RECORD_XPATH, ConfigurationPage::SET_SPF_RECORD_CSS) => true
        ));

        // Create new client account
        $account = $I->createNewAccount();

        // Click on Edit DNS ZOne command
        $I->searchAndClickCommand('Edit DNS Zone');

        // Select client account domain from list
        $I->selectOption('domainselect', $account['domain']);

        // Click the edit button
        $I->click('Edit');

        // Check if the spf record is set
        $I->seeInField('input', '"'.$spfRecord.'"');
    }

    /**
     * Verify if domain list refresh for reseller level
     */
    public function verifyDomainListRefreshForResellerLevel(ConfigurationSteps $I)
    {
        // Create a new reseller account
        $reseller = $I->createNewAccount(['reseller' => true]);

        // Login as reseller
        $I->login($reseller['username'], $reseller['password']);

        // Go to configuration page
        $I->goToPage(ProfessionalSpamFilterPage::CONFIGURATION_BTN, ConfigurationPage::TITLE);

        // Set plugin configuration options
        $I->setConfigurationOptions(array(
            Locator::combine(ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_XPATH, ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_CSS) => true
        ));

        // Create a new client account from the UI
        $account = $I->createNewAccount(['ui' => true]);

        // Login as client
        $I->loginAsClient($account['username'], $account['password']);

        // Create a new alias domain as client
        $aliasDomain = $I->addAliasDomainAsClient($account['domain']);

        // Check if alias domain exist in plugin domain list as client
        $I->checkDomainListAsClient($aliasDomain);

        // Login as reseller
        $I->login($reseller['username'], $reseller['password']);

        // Go to the plugin configuration page
        $I->goToPage(ProfessionalSpamFilterPage::CONFIGURATION_BTN, ConfigurationPage::TITLE);

        // Restore the plugin configuration options
        $I->setConfigurationOptions(array(
            Locator::combine(ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_XPATH, ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_CSS) => false
        ));
    }
}
