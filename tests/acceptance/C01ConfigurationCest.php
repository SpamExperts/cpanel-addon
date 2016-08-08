<?php

use Page\DomainListPage;
use Page\ConfigurationPage;
use Page\ProfessionalSpamFilterPage;
use Step\Acceptance\ConfigurationSteps;
use Codeception\Util\Locator;

class C01ConfigurationCest
{
    public function _before(ConfigurationSteps $I)
    {
        $I->loginAsRoot();
        $I->goToPage(ProfessionalSpamFilterPage::CONFIGURATION_BTN, ConfigurationPage::TITLE);
    }

    public function _after(ConfigurationSteps $I)
    {
        $I->removeCreatedAccounts();
    }

    public function _failed(ConfigurationSteps $I)
    {
        $this->_after($I);
    }

    /**
     * Verify the 'Configuration page' layout and functionality - CHECKED
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
     * Verify "Automatically add domains to the Spamfilter" option works properly when unchecked - CHECKED
     */
    public function verifyNotAutomaticallyAddDomainToPsf(ConfigurationSteps $I)
    {
        // Set configuration options
        $I->setConfigurationOptions(array(
            Locator::combine(ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT_XPATH, ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT_CSS) => false
        ));

        // Create new client account
        $account = $I->createNewAccount();

        // Cgeck if domain is not present in filter
        $I->checkDomainIsNotPresentInFilter($account['domain']);
    }

    /**
     * Verify "Automatically add domains to the Spamfilter" option works properly when checked - CHECKED
     */
    public function verifyAutomaticallyAddDomainToPsf(ConfigurationSteps $I)
    {
        // Set configuration options
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
     * Verify "Automatically delete domains from the SpamFilter" option works properly when unchecked - CHECKED
     */
    public function verifyNotAutomaticallyDeleteDomainToPsf(ConfigurationSteps $I)
    {
        // Set configuration options
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
     * Verify if client account is removed all the domains created by him will be removed from spampanel - CHECKED
     */
    public function verifyAutomaticallyDeleteDomainToPsf(ConfigurationSteps $I)
    {
        // Set configuration options
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
     * Verify if domains removed by client are removed from spampanel - CHECKED
     */
    public function verifyAutmaticallyDeleteSecondaryDomains(ConfigurationSteps $I)
    {
        // Set configuration options
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
     * Verify "Automatically change the MX records for domains" option works properly when unchecked - CHECKED
     */
    public function verifyNotAutomaticallyChangeMxRecords(ConfigurationSteps $I)
    {
        // Set configuration options
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
     * Verify "Automatically change the MX records for domains" option works properly when checked - CHECKED
     */
    public function verifyAutomaticallyChangeMxRecords(ConfigurationSteps $I)
    {
        // Set configuration options
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

    public function verifyHookSetEmailRoutingAtClientLevel(ConfigurationSteps $I)
    {
        $I->setConfigurationOptions(array(
            Locator::combine(ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT_XPATH, ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT_CSS) => true,
            Locator::combine(ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_XPATH, ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_CSS) => true,
            Locator::combine(ConfigurationPage::DO_NOT_PROTECT_REMOTE_DOMAINS_OPT_XPATH, ConfigurationPage::DO_NOT_PROTECT_REMOTE_DOMAINS_OPT_CSS) => false,
            Locator::combine(ConfigurationPage::AUTOMATICALLY_DELETE_DOMAINS_OPT_XPATH, ConfigurationPage::AUTOMATICALLY_DELETE_DOMAINS_OPT_CSS) => true,
            Locator::combine(ConfigurationPage::ADD_REMOVE_DOMAIN_XPATH, ConfigurationPage::ADD_REMOVE_DOMAIN_CSS) => true
        ));

        $spampanelMxRecords = $I->getMxFields();
        $account = $I->createNewAccount();

        // Change email routing and remove account by using Backup Mail Exchanger
        $I->loginAsClient($account['username'], $account['password']);
        $destinationRoutes = $I->makeSpampanelApiRequest()->getDomainRoutesNames($account['domain']);
        $I->accessEmailRoutingInMxEntryPage();
        $I->ChangeEmailRoutingInMxEntryPageToBackupMailExchanger();
        $I->logoutAsClient();
        $I->loginAsRoot();
        $I->assertDomainNotExistsInSpampanel($account['domain']);
        $I->seeMxEntriesInCpanelInterface($account['domain'], $destinationRoutes);
        $I->dontSeeMxEntriesInCpanelInterface($account['domain'], $spampanelMxRecords);

        // Change email routing and remove account by using Local Mail Exchanger
        $I->loginAsClient($account['username'], $account['password']);
        $I->accessEmailRoutingInMxEntryPage();
        $I->pauseExecution();
        $I->ChangeEmailRoutingInMxEntryPageToLocalMailExchanger();
        $I->logoutAsClient();
        $I->loginAsRoot();
        $I->searchDomainList($account['domain']);
        $I->assertDomainExistsInSpampanel($account['domain']);
        $I->seeMxEntriesInCpanelInterface($account['domain'], $spampanelMxRecords);

        // Change email routing and remove account by using Remote Mail Exchanger
        $I->loginAsClient($account['username'], $account['password']);
        $destinationRoutes = $I->makeSpampanelApiRequest()->getDomainRoutesNames($account['domain']);
        $I->accessEmailRoutingInMxEntryPage();
        $I->ChangeEmailRoutingInMxEntryPageToRemoteMailExchanger();
        $I->logoutAsClient();
        $I->loginAsRoot();
        $I->searchDomainList($account['domain']);
        $I->assertDomainNotExistsInSpampanel($account['domain']);
        $I->seeMxEntriesInCpanelInterface($account['domain'], $destinationRoutes);
        $I->dontSeeMxEntriesInCpanelInterface($account['domain'], $spampanelMxRecords);
    }

    public function verifyHookSetEmailRoutingWhileUsingBulkprotect(ConfigurationSteps $I)
    {
        $I->setConfigurationOptions(array(
            Locator::combine(ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT_XPATH, ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT_CSS) => false,
            Locator::combine(ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_XPATH, ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_CSS) => true,
            Locator::combine(ConfigurationPage::DO_NOT_PROTECT_REMOTE_DOMAINS_OPT_XPATH, ConfigurationPage::DO_NOT_PROTECT_REMOTE_DOMAINS_OPT_CSS) => false,
            Locator::combine(ConfigurationPage::AUTOMATICALLY_DELETE_DOMAINS_OPT_XPATH, ConfigurationPage::AUTOMATICALLY_DELETE_DOMAINS_OPT_CSS) => true,
            Locator::combine(ConfigurationPage::ADD_REMOVE_DOMAIN_XPATH, ConfigurationPage::ADD_REMOVE_DOMAIN_CSS) => true
        ));

        $I->removeAllAccounts();

        $account = $I->createNewAccount();

        // Change email routing to other than Local
        $I->loginAsClient($account['username'], $account['password']);
        $I->accessEmailRoutingInMxEntryPage();
        $I->verifyEmailRoutingInMxEntryPageSetToLocal();
        $I->ChangeEmailRoutingInMxEntryPageToBackupMailExchanger();
        $I->verifyEmailRoutingInMxEntryPageSetToBackup();
        $I->logoutAsClient();
        $I->loginAsRoot();
        $I->searchDomainList($account['domain']);

        // Email routing should not change when domain is protected
        $I->click(DomainListPage::TOGGLE_PROTECTION_LINK);
        $I->waitForText("The protection status of {$account['domain']} has been changed to protected", 60);
        $I->logout();
        $I->loginAsClient($account['username'], $account['password']);
        $I->accessEmailRoutingInMxEntryPage();
        $I->verifyEmailRoutingInMxEntryPageSetToLocal();
        $I->logoutAsClient();
        $I->loginAsRoot();
        $I->searchDomainList($account['domain']);

        // Email routing should not change when domain is unprotected
        $I->click(DomainListPage::TOGGLE_PROTECTION_LINK);
        $I->waitForText("The protection status of {$account['domain']} has been changed to unprotected", 60);
        $I->loginAsClient($account['username'], $account['password']);
        $I->accessEmailRoutingInMxEntryPage();
        $I->verifyEmailRoutingInMxEntryPageSetToLocal();
        $I->logoutAsClient();
        $I->loginAsRoot();
        $I->searchDomainList($account['domain']);

        // Email routing should not change when domain is protected again
        $I->click(DomainListPage::TOGGLE_PROTECTION_LINK);
        $I->waitForText("The protection status of {$account['domain']} has been changed to protected", 60);
        $I->loginAsClient($account['username'], $account['password']);
        $I->accessEmailRoutingInMxEntryPage();

        // Email routing is changed manually
        $I->ChangeEmailRoutingInMxEntryPageToRemoteMailExchanger();
        $I->verifyEmailRoutingInMxEntryPageSetToRemote();
        $I->logoutAsClient();
        $I->loginAsRoot();
        $I->searchDomainList($account['domain']);
        $I->checkProtectionStatusIs(DomainListPage::STATUS_DOMAIN_IS_NOT_PRESENT_IN_THE_FILTER);

        // Email routing is changed to Local when bulkprotect is ran
        $I->goToPage(\Page\ProfessionalSpamFilterPage::BULKPROTECT_BTN, \Page\BulkprotectPage::TITLE);
        $I->seeBulkProtectLastExecutionInfo();
        $I->submitBulkprotectForm();
        $I->seeBulkprotectRanSuccessfully();
        $I->see('Domain has been added', \Page\BulkprotectPage::TABLE);
        $I->logout();
        $I->loginAsClient($account['username'], $account['password']);
        $I->accessEmailRoutingInMxEntryPage();
        $I->verifyEmailRoutingInMxEntryPageSetToLocal();
    }

    /**
     * Verify "Configure the email address for this domain" option works properly when unchecked
     */
    public function verifyNotConfigureTheEmailAddressForThisDomainOption(ConfigurationSteps $I)
    {
        $I->setConfigurationOptions(array(
            Locator::combine(ConfigurationPage::CONFIGURE_EMAIL_ADDRESS_OPT_XPATH, ConfigurationPage::CONFIGURE_EMAIL_ADDRESS_OPT_CSS) => false
        ));

        $account = $I->createNewAccount();

        $I->searchDomainList($account['domain']);
        $I->loginOnSpampanel($account['domain']);
        $I->click('Domain settings');
        $I->dontSeeInField('#contact_email', $account['email']);
        $I->loginAsRoot();
    }

    /**
     * Verify "Configure the email address for this domain" option works properly when checked
     */
    public function verifyConfigureTheEmailAddressForThisDomainOption(ConfigurationSteps $I)
    {
        $I->setConfigurationOptions(array(
            Locator::combine(ConfigurationPage::CONFIGURE_EMAIL_ADDRESS_OPT_XPATH, ConfigurationPage::CONFIGURE_EMAIL_ADDRESS_OPT_CSS) => true
        ));

        $account = $I->createNewAccount();
        $I->searchDomainList($account['domain']);
        $I->loginOnSpampanel($account['domain']);
        $I->click('Domain settings');
        $I->seeInField('#contact_email', $account['email']);
        $I->loginAsRoot();
    }

    /**
     * Verify 'Use existing MX records as routes in the spamfilter' option works properly when unchecked
     */
    public function verifyUseExistingMXRecordsAsRoutesInTheSpamfilterOption(ConfigurationSteps $I)
    {
        $I->setConfigurationOptions(array(
            Locator::combine(ConfigurationPage::USE_EXISTING_MX_OPT_XPATH, ConfigurationPage::USE_EXISTING_MX_OPT_CSS) => true
        ));

        $account = $I->createNewAccount();
        $routes = $I->makeSpampanelApiRequest()->getDomainRoutes($account['domain']);
        $I->assertContains($account['domain'].'::25', $routes);
    }

    /**
     * Verify 'Use existing MX records as routes in the spamfilter' option works properly when checked
     */
    public function verifyNotUseExistingMXRecordsAsRoutesInTheSpamfilterOption(ConfigurationSteps $I)
    {
        $I->setConfigurationOptions(array(
            Locator::combine(ConfigurationPage::USE_EXISTING_MX_OPT_XPATH, ConfigurationPage::USE_EXISTING_MX_OPT_CSS) => false
        ));

        $account = $I->createNewAccount();
        $routes = $I->makeSpampanelApiRequest()->getDomainRoutes($account['domain']);
        $I->assertContains($I->getEnvHostname().'::25', $routes);
    }

    /**
     * Verify 'Use IP as destination route instead of domain' option works properly when checked
     */
    public function verifyUseIPAsDestinationRouteInsteadOfDomainOption(ConfigurationSteps $I)
    {
        $I->setConfigurationOptions(array(
            Locator::combine(ConfigurationPage::USE_EXISTING_MX_OPT_XPATH, ConfigurationPage::USE_EXISTING_MX_OPT_CSS) => false,
            Locator::combine(ConfigurationPage::USE_IP_AS_DESTINATION_OPT_XPATH, ConfigurationPage::USE_IP_AS_DESTINATION_OPT_CSS) => true
        ));

        $account = $I->createNewAccount();
        $ip = gethostbyname($I->getEnvHostname());
        $routes = $I->makeSpampanelApiRequest()->getDomainRoutes($account['domain']);
        $I->assertContains($ip.'::25', $routes);
    }

    /**
     * Verify 'Use IP as destination route instead of domain' option works properly when unchecked
     */
    public function verifyNotUseIPAsDestinationRouteInsteadOfDomainOption(ConfigurationSteps $I)
    {
        $I->setConfigurationOptions(array(
            Locator::combine(ConfigurationPage::USE_IP_AS_DESTINATION_OPT_XPATH, ConfigurationPage::USE_IP_AS_DESTINATION_OPT_CSS) => false,
            Locator::combine(ConfigurationPage::USE_EXISTING_MX_OPT, ConfigurationPage::USE_EXISTING_MX_OPT_CSS) => true,
        ));

        $account = $I->createNewAccount();
        $routes = $I->makeSpampanelApiRequest()->getDomainRoutes($account['domain']);
        $I->assertContains($account['domain'].'::25', $routes);
    }

    /**
     * Verify 'Process addon-, parked and subdomains' option works properly for addon domains when checked
     */
    public function verifyAddonDomains(ConfigurationSteps $I)
    {
        $I->setConfigurationOptions(array(
            Locator::combine(ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_XPATH, ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_CSS) => true
        ));

        $I->createDefaultPackage();

        $account = $I->createNewAccount();

        // addon domain
        $I->loginAsClient($account['username'], $account['password']);
        $addonDomainName = $I->addAddonDomainAsClient($account['domain']);
        $I->loginAsRoot();
        $I->searchDomainList($addonDomainName);
        $I->see('addon', DomainListPage::TYPE_COLUMN_FROM_FIRST_ROW);
        $I->assertDomainExistsInSpampanel($addonDomainName);
    }

    /**
     * Verify 'Process addon-, parked and subdomains' option works properly for subdomains when checked
     */
    public function verifySubDomains(ConfigurationSteps $I)
    {
        $I->setConfigurationOptions(array(
            Locator::combine(ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_XPATH, ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_CSS) => true
        ));

        $I->createDefaultPackage();

        $account = $I->createNewAccount();

        // subdomain
        $I->loginAsClient($account['username'], $account['password']);
        $subDomain = $I->addSubdomainAsClient($account['domain']);
        $I->loginAsRoot();
        $I->searchDomainList($subDomain);
        $I->see('subdomain', DomainListPage::TYPE_COLUMN_FROM_FIRST_ROW);
        $I->assertDomainExistsInSpampanel($subDomain);
    }

    /**
     * Verify 'Process addon-, parked and subdomains' option works properly for parked domains when checked
     */
    public function verifyParkedDomains(ConfigurationSteps $I)
    {
        $I->setConfigurationOptions(array(
            Locator::combine(ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_XPATH, ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_CSS) => true,
            Locator::combine(ConfigurationPage::DO_NOT_PROTECT_REMOTE_DOMAINS_OPT_XPATH, ConfigurationPage::DO_NOT_PROTECT_REMOTE_DOMAINS_OPT_CSS) => false
        ));

        $I->createDefaultPackage();

        $account = $I->createNewAccount();

        // parked domain
        $I->loginAsClient($account['username'], $account['password']);
        $parkedDomain = $I->addParkedDomainAsClient($account['domain']);
        $I->loginAsRoot();
        $I->searchDomainList($parkedDomain);
        $I->see('parked', DomainListPage::TYPE_COLUMN_FROM_FIRST_ROW);
        $I->assertDomainExistsInSpampanel($parkedDomain);
    }

    /**
     * Verify 'Redirect back to Cpanel upon logout' option works properly when checked
     */
    public function verifyRedirectBackToCpanelUponLogout(ConfigurationSteps $I)
    {
        $I->setConfigurationOptions(array(
            Locator::combine(ConfigurationPage::REDIRECT_BACK_TO_CPANEL_OPT_XPATH, ConfigurationPage::REDIRECT_BACK_TO_CPANEL_OPT_CSS) => true
        ));

        $account = $I->createNewAccount();

        $I->loginAsClient($account['username'], $account['password']);
        $I->checkDomainListAsClient($account['domain']);
        $I->loginOnSpampanel($account['domain']);
        $I->logoutFromSpampanel();
        $I->seeInCurrentAbsoluteUrl($I->getEnvHostname());

        $I->logoutAsClient();
        $I->loginAsRoot();
        $I->searchDomainList($account['domain']);
        $I->loginOnSpampanel($account['domain']);
        $I->logoutFromSpampanel();
        $I->seeInCurrentAbsoluteUrl($I->getEnvHostname());
    }

    /**
     * Verify 'Add addon-, parked and subdomains as an alias instead of a normal domain' option works properly when checked
     */
    public function verifyAddAddonParkedAndSubdomainsAsAnAliasInsteadOfANormalDomain(ConfigurationSteps $I)
    {
        $I->setConfigurationOptions(array(
            Locator::combine(ConfigurationPage::ADD_ADDON_AS_ALIAS_CPANEL_OPT_XPATH, ConfigurationPage::ADD_ADDON_AS_ALIAS_CPANEL_OPT_CSS) => true,
            Locator::combine(ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_XPATH, ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_CSS) => true,
            Locator::combine(ConfigurationPage::DO_NOT_PROTECT_REMOTE_DOMAINS_OPT_XPATH, ConfigurationPage::DO_NOT_PROTECT_REMOTE_DOMAINS_OPT_CSS) => false
        ));

        $account = $I->createNewAccount();

        $I->loginAsClient($account['username'], $account['password']);
        $addonDomainName = $I->addAddonDomainAsClient($account['domain']);
        $I->assertIsAliasInSpampanel($addonDomainName, $account['domain']);

        $parkedDomain = $I->addParkedDomainAsClient($account['domain']);
        $I->assertIsAliasInSpampanel($parkedDomain, $account['domain']);

        $subDomain = $I->addSubdomainAsClient($account['domain']);
        $I->assertIsAliasInSpampanel($subDomain, $account['domain']);
    }

    /**
     * Verify mx records are restored properly in cpanel when a domain is unprotected
     */
    public function verifyAllRoutesFromSpampanelAreAddedInCpanelOnDomainUnprotect(ConfigurationSteps $I)
    {
        $I->setConfigurationOptions(array(
            Locator::combine(ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT_XPATH, ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT_CSS) => true
        ));

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
        $spfRecord = 'v=spf1 a:' . PsfConfig::getApiHostname();
        $I->setFieldSpfRecord($spfRecord);
        $I->setConfigurationOptions([
            Locator::combine(ConfigurationPage::SET_SPF_RECORD_XPATH, ConfigurationPage::SET_SPF_RECORD_CSS) => true
        ]);

        $account = $I->createNewAccount();

        $I->searchAndClickCommand('Edit DNS Zone');
        $I->selectOption('domainselect', $account['domain']);
        $I->click('Edit');
        $I->seeInField('input', '"'.$spfRecord.'"');
    }

     /**
     * Verify
     */
    public function verifyDomainListRefreshForCustomerLevel(ConfigurationSteps $I)
    {
        $I->setConfigurationOptions(array(
            Locator::combine(ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_XPATH, ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_CSS) => true
        ));

        $I->createDefaultPackage();

        $account = $I->createNewAccount();

        // addon domain
        $I->loginAsClient($account['username'], $account['password']);

        // add alias domain
        $aliasDomain = $I->addAliasDomainAsClient($account['domain']);
        $I->clickHomeMenuLink();

        $I->checkDomainListAsClient($aliasDomain);
        $I->logoutAsClient();
        $I->loginAsRoot();

        $I->searchDomainList($aliasDomain);
        $I->see('alias', DomainListPage::TYPE_COLUMN_FROM_FIRST_ROW);
        $I->assertDomainExistsInSpampanel($aliasDomain);
    }

    /**
     * Verify
     */
    public function verifyDomainListRefreshForResellerLevel(ConfigurationSteps $I)
    {
        $reseller = $I->createNewAccount(['reseller' => true]);
        $I->login($reseller['username'], $reseller['password']);

        $I->setConfigurationOptions(array(
            Locator::combine(ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_XPATH, ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_CSS) => true
        ));

        $I->createDefaultPackage();

        $account = $I->createNewAccount(['ui' => true]);
        $I->loginAsClient($account['username'], $account['password']);

        // addon domain
        $aliasDomain = $I->addAliasDomainAsClient($account['domain']);

        // add alias domain
        $I->clickHomeMenuLink();
        $I->checkDomainListAsClient($aliasDomain);

        $I->login($reseller['username'], $reseller['password']);
        $I->goToPage(ProfessionalSpamFilterPage::CONFIGURATION_BTN, ConfigurationPage::TITLE);
        $I->setConfigurationOptions(array(
            Locator::combine(ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_XPATH, ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_CSS) => false
        ));

    }
}
