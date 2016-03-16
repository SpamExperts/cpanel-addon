<?php

use Pages\CpanelClientPage;
use Pages\DomainListPage;
use Pages\ConfigurationPage;
use Pages\ProfessionalSpamFilterPage;
use Step\Acceptance\ConfigurationSteps;

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

    public function checkConfigurationPage(ConfigurationSteps $I)
    {
        $I->verifyPageLayout();
        $I->setFieldApiUrl(PsfConfig::getApiUrl());
        $I->setFieldApiHostname(PsfConfig::getApiHostname());
        $I->setFieldApiUsernameIfEmpty(PsfConfig::getApiUsername());
        $I->setFieldApiPassword(PsfConfig::getApiPassword());
        $I->setFieldPrimaryMX(PsfConfig::getPrimaryMX());

        $I->submitSettingForm();
        $I->seeSubmissionIsSuccessful();
    }

    /**
     * Verify "Automatically add domains to the Spamfilter" option works properly when unchecked
     */
    public function verifyNotAutomaticallyAddDomainToPsf(ConfigurationSteps $I)
    {
        $I->setConfigurationOptions(array(
            ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT => false
        ));
        $account = $I->createNewAccount();
        $I->checkDomainIsNotPresentInFilter($account['domain']);
    }

    /**
     * Verify "Automatically add domains to the Spamfilter" option works properly when checked
     */
    public function verifyAutomaticallyAddDomainToPsf(ConfigurationSteps $I)
    {
        $I->setConfigurationOptions(array(
            ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT => true
        ));
        $account = $I->createNewAccount();
        $I->checkDomainIsPresentInFilter($account['domain']);
    }

    /**
     * Verify "Automatically delete domains from the SpamFilter" option works properly when unchecked
     */
    public function verifyNotAutomaticallyDeleteDomainToPsf(ConfigurationSteps $I)
    {
        $I->setConfigurationOptions(array(
            ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT => true,
            ConfigurationPage::AUTOMATICALLY_DELETE_DOMAINS_OPT => false

        ));
        $account = $I->createNewAccount();
        $domainExists = $I->makeSpampanelApiRequest()->domainExists($account['domain']);
        $I->assertTrue($domainExists);
        $I->removeAccount($account['username']);
        $domainExists = $I->makeSpampanelApiRequest()->domainExists($account['domain']);
        $I->assertTrue($domainExists);
    }

    /**
     * Verify "Automatically delete domains from the SpamFilter" option works properly when checked
     */
    public function verifyAutomaticallyDeleteDomainToPsf(ConfigurationSteps $I)
    {
        $I->setConfigurationOptions(array(
            ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT => true,
            ConfigurationPage::AUTOMATICALLY_DELETE_DOMAINS_OPT => true

        ));
        $account = $I->createNewAccount();
        $domainExists = $I->makeSpampanelApiRequest()->domainExists($account['domain']);
        $I->assertTrue($domainExists);
        $I->removeAccount($account['username']);
        $domainExists = $I->makeSpampanelApiRequest()->domainExists($account['domain']);
        $I->assertFalse($domainExists);
    }

    /**
     * Verify "Automatically change the MX records for domains" option works properly when unchecked
     */
    public function verifyNotAutomaticallyChangeMxRecords(ConfigurationSteps $I)
    {
        $I->setConfigurationOptions(array(
            ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT => true,
            ConfigurationPage::AUTOMATICALLY_CHANGE_MX_OPT => false
        ));

        $account = $I->createNewAccount();

        $I->searchAndClickCommand('Edit MX Entry');
        $I->selectOption('domainselect', $account['domain']);
        $I->click('Edit');
        $I->dontSeeInField('#mxlisttb input', PsfConfig::getPrimaryMX());
    }

    /**
     * Verify "Automatically change the MX records for domains" option works properly when checked
     */
    public function verifyAutomaticallyChangeMxRecords(ConfigurationSteps $I)
    {
        $I->setConfigurationOptions(array(
            ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT => true,
            ConfigurationPage::AUTOMATICALLY_CHANGE_MX_OPT => true
        ));

        $account = $I->createNewAccount();

        $I->searchAndClickCommand('Edit MX Entry');
        $I->selectOption('domainselect', $account['domain']);
        $I->click('Edit');
        $I->seeInField('#mxlisttb input', PsfConfig::getPrimaryMX());
    }

    /**
     * Verify "Configure the email address for this domain" option works properly when unchecked
     */
    public function verifyNotConfigureTheEmailAddressForThisDomainOption(ConfigurationSteps $I)
    {
        $I->setConfigurationOptions(array(
            ConfigurationPage::CONFIGURE_EMAIL_ADDRESS_OPT=> false
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
            ConfigurationPage::CONFIGURE_EMAIL_ADDRESS_OPT=> true
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
            ConfigurationPage::USE_EXISTING_MX_OPT => true
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
            ConfigurationPage::USE_EXISTING_MX_OPT => false
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
            ConfigurationPage::USE_EXISTING_MX_OPT => false,
            ConfigurationPage::USE_IP_AS_DESTINATION_OPT => true
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
            ConfigurationPage::USE_IP_AS_DESTINATION_OPT => false,
            ConfigurationPage::USE_EXISTING_MX_OPT => true,
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
            ConfigurationPage::PROCESS_ADDON_CPANEL_OPT => true
        ));

        $I->createDefaultPackage();

        $account = $I->createNewAccount();

        // addon domain
        $I->loginAsClient($account['username'], $account['password']);
        $addonDomainName = $I->addAddonDomainAsClient($account['domain']);
        $I->loginAsRoot();
        $I->searchDomainList($addonDomainName);
        $I->see('addon', DomainListPage::TYPE_COLUMN_FROM_FIRST_ROW);
    }

    /**
     * Verify 'Process addon-, parked and subdomains' option works properly for subdomains when checked
     */
    public function verifySubDomains(ConfigurationSteps $I)
    {
        $I->setConfigurationOptions(array(
            ConfigurationPage::PROCESS_ADDON_CPANEL_OPT => true
        ));

        $I->createDefaultPackage();

        $account = $I->createNewAccount();

        // subdomain
        $I->loginAsClient($account['username'], $account['password']);
        $subDomain = $I->addSubdomainAsClient($account['domain']);
        $I->loginAsRoot();
        $I->searchDomainList($subDomain);
        $I->see('subdomain', DomainListPage::TYPE_COLUMN_FROM_FIRST_ROW);
    }

    /**
     * Verify 'Process addon-, parked and subdomains' option works properly for parked domains when checked
     */
    public function verifyParkedDomains(ConfigurationSteps $I)
    {
        $I->setConfigurationOptions(array(
            ConfigurationPage::PROCESS_ADDON_CPANEL_OPT => true
        ));

        $I->createDefaultPackage();

        $account = $I->createNewAccount();

        // parked domain
        $I->loginAsClient($account['username'], $account['password']);
        $parkedDomain = $I->addParkedDomainAsClient($account['domain']);
        $I->loginAsRoot();
        $I->searchDomainList($parkedDomain);
        $I->see('parked', DomainListPage::TYPE_COLUMN_FROM_FIRST_ROW);
    }

    /**
     * Verify 'Redirect back to Cpanel upon logout' option works properly when checked
     */
    public function verifyRedirectBackToCpanelUponLogout(ConfigurationSteps $I)
    {
        $I->setConfigurationOptions(array(
            ConfigurationPage::REDIRECT_BACK_TO_CPANEL_OPT => true
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
            ConfigurationPage::ADD_ADDON_CPANEL_OPT => true
        ));

        $account = $I->createNewAccount();

        $I->loginAsClient($account['username'], $account['password']);
        $addonDomainName = $I->addAddonDomainAsClient($account['domain']);

        $aliases = $I->makeSpampanelApiRequest()->getDomainAliases($account['domain']);
        $I->assertContains($addonDomainName, $aliases);
        $I->clickHomeMenuLink();
        $parkedDomain = $I->addParkedDomainAsClient($account['domain']);
        $aliases = $I->makeSpampanelApiRequest()->getDomainAliases($account['domain']);
        $I->assertContains($parkedDomain, $aliases);

//        Waiting for #26940
//        $I->click('#lnkMenu');
//        $subDomain = $I->addSubdomainAsClient($account['domain']);
//        $aliases = $I->makeSpampanelApiRequest()->getDomainAliases($account['domain']);
//        $I->assertContains($subDomain, $aliases);
    }

    /**
     * Verify mx records are restored properly in cpanel when a domain is unprotected
     */
    public function verifyAllRoutesFromSpampanelAreAddedInCpanelOnDomainUnprotect(ConfigurationSteps $I)
    {
        $I->setConfigurationOptions(array(
            ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT => true
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
            ConfigurationPage::SET_SPF_RECORD => true
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
            ConfigurationPage::PROCESS_ADDON_CPANEL_OPT => true
        ));

        $I->createDefaultPackage();

        $account = $I->createNewAccount();

        // addon domain
        $I->loginAsClient($account['username'], $account['password']);
        $parkedDomain = $I->addParkedDomainAsClient($account['domain']);
        $addonDomainName = $I->addAddonDomainAsClient($account['domain']);
        $I->clickHomeMenuLink();
        $subDomain = $I->addSubdomainAsClient($account['domain']);
        $I->clickHomeMenuLink();
        $I->checkDomainListAsClient([$parkedDomain, $addonDomainName, $subDomain]);

        $I->loginAsRoot();
        $I->goToPage(ProfessionalSpamFilterPage::CONFIGURATION_BTN, ConfigurationPage::TITLE);
        $I->setConfigurationOptions(array(
            ConfigurationPage::PROCESS_ADDON_CPANEL_OPT => false
        ));

        $I->loginAsClient($account['username'], $account['password']);

        $I->checkDomainListAsClient($account['domain']);
        $I->dontSeeInDomainTable($parkedDomain);
        $I->dontSeeInDomainTable($addonDomainName);
        $I->dontSeeInDomainTable($subDomain);
    }

    /**
     * Verify
     */
    public function verifyDomainListRefreshForResellerLevel(ConfigurationSteps $I)
    {
        $reseller = $I->createNewAccount(['reseller' => true]);
        $I->login($reseller['username'], $reseller['password']);

        $I->setConfigurationOptions(array(
            ConfigurationPage::PROCESS_ADDON_CPANEL_OPT => true
        ));

        $I->createDefaultPackage();

        $account = $I->createNewAccount(['ui' => true]);

        // addon domain
        $I->loginAsClient($account['username'], $account['password']);
        $parkedDomain = $I->addParkedDomainAsClient($account['domain']);
        $addonDomainName = $I->addAddonDomainAsClient($account['domain']);
        $I->clickHomeMenuLink();
        $subDomain = $I->addSubdomainAsClient($account['domain']);
        $I->clickHomeMenuLink();
        $I->checkDomainListAsClient([$parkedDomain, $addonDomainName, $subDomain]);

        $I->login($reseller['username'], $reseller['password']);
        $I->goToPage(ProfessionalSpamFilterPage::CONFIGURATION_BTN, ConfigurationPage::TITLE);
        $I->setConfigurationOptions(array(
            ConfigurationPage::PROCESS_ADDON_CPANEL_OPT => false
        ));

        $I->loginAsClient($account['username'], $account['password']);

        $I->checkDomainListAsClient($account['domain']);
        $I->dontSeeInDomainTable($parkedDomain);
        $I->dontSeeInDomainTable($addonDomainName);
        $I->dontSeeInDomainTable($subDomain);

//        $I->searchDomainList($addonDomainName);
//        $I->see('addon', DomainListPage::TYPE_COLUMN_FROM_FIRST_ROW);
        $I->pauseExecution();

    }
}
