<?php

use Page\ConfigurationPage;
use Page\DomainListPage;
use Step\Acceptance\CommonSteps;
use Codeception\Util\Locator;

class C08ToggleProtectionCest
{
    public function _before(CommonSteps $I)
    {
        $I->loginAsRoot();
    }

    public function _after(CommonSteps $I)
    {
        $I->removeCreatedAccounts();
    }

    public function _failed(CommonSteps $I)
    {
        $this->_after($I);
    }

    public function testToggleProtectionErrorAddedAsAliasNotDomain(CommonSteps $I)
    {
        $setup = $this->setupErrorAddedAsAliasNotDomainScenario($I);
        $I->pauseExecution();
        $addonDomainName = $setup['addon_domain_name'];

        // Test
        $I->searchDomainList($addonDomainName);
        $I->click(DomainListPage::TOGGLE_PROTECTION_LINK);
        $message = "The protection status of $addonDomainName could not be changed to unprotected because subdomain, parked and addon domains are treated as normal domains and \"$addonDomainName\" is already added as an alias.";
        $I->waitForText($message, 60);
    }

    public function testHookErrorAddedAsAliasNotDomain(CommonSteps $I)
    {
        $setup = $this->setupErrorAddedAsAliasNotDomainScenario($I);

        $addonDomainName = $setup['addon_domain_name'];
        $account = $setup['account'];

        $I->assertDomainExistsInSpampanel($addonDomainName);
        $I->loginAsClient($account['username'], $account['password']);
        $I->removeAddonDomainAsClient($addonDomainName);
        $I->assertDomainExistsInSpampanel($addonDomainName);
    }

    public function testToggleProtectionErrorAddedAsDomainNotAlias(CommonSteps $I)
    {
        $setup = $this->setupErrorAddedAsDomainNotAliasScenario($I);
        $addonDomainName = $setup['addon_domain_name'];

        // Test
        $I->searchDomainList($addonDomainName);
        $I->checkProtectionStatusIs(DomainListPage::STATUS_DOMAIN_IS_PRESENT_IN_THE_FILTER);
        $I->click(DomainListPage::TOGGLE_PROTECTION_LINK);
        $message = "The protection status of $addonDomainName could not be changed to unprotected because subdomain, parked and addon domains are treated as aliases and \"$addonDomainName\" is already added as a normal domain.";
        $I->waitForText($message, 60);
    }

    public function testHookErrorAddedAsDomainNotAlias(CommonSteps $I)
    {
        $setup = $this->setupErrorAddedAsDomainNotAliasScenario($I);
        $addonDomainName = $setup['addon_domain_name'];
        $account = $setup['account'];

        // Test
        $I->assertDomainExistsInSpampanel($addonDomainName);
        $I->loginAsClient($account['username'], $account['password']);
        $I->removeAddonDomainAsClient($addonDomainName);
        $I->assertDomainExistsInSpampanel($addonDomainName);
    }

    public function testToggleAsAliasAndUntoggleAlias(CommonSteps $I)
    {
        $I->goToConfigurationPageAndSetOptions([
            Locator::combine(ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT_XPATH, ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT_CSS) => true,
            Locator::combine(ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_XPATH, ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_CSS) => true,
            Locator::combine(ConfigurationPage::ADD_ADDON_AS_ALIAS_CPANEL_OPT_XPATH, ConfigurationPage::ADD_ADDON_AS_ALIAS_CPANEL_OPT_CSS) => true,
        ]);

        $account = $I->createNewAccount();
        $domain = $account['domain'];
        $I->loginAsClient($account['username'], $account['password']);
        $addonDomainName = $I->addAddonDomainAsClient($domain);
        $I->assertDomainExistsInSpampanel($addonDomainName);
        $I->assertIsAliasInSpampanel($addonDomainName, $domain);

        $I->loginAsRoot();
        $I->searchDomainList($addonDomainName);
        $I->click(DomainListPage::TOGGLE_PROTECTION_LINK);
        $I->checkProtectionStatusIs(DomainListPage::STATUS_DOMAIN_IS_NOT_PRESENT_IN_THE_FILTER);
        $I->assertDomainNotExistsInSpampanel($addonDomainName);
        $I->assertIsNotAliasInSpampanel($addonDomainName, $domain);
    }

    private function setupErrorAddedAsAliasNotDomainScenario(CommonSteps $I)
    {
        // setup
        $I->goToConfigurationPageAndSetOptions([
            Locator::combine(ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT_XPATH, ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT_CSS) => false,
            Locator::combine(ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_XPATH, ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_CSS) => true,
        ]);

        $account = $I->createNewAccount();
        $domain = $account['domain'];

        $I->searchDomainList($domain);
        $I->click(DomainListPage::TOGGLE_PROTECTION_LINK);
        $I->waitForText("The protection status of $domain has been changed to protected", 60);
        $I->checkProtectionStatusIs(DomainListPage::STATUS_DOMAIN_IS_PRESENT_IN_THE_FILTER);

        $I->loginAsClient($account['username'], $account['password']);
        $addonDomainName = $I->addAddonDomainAsClient($domain);

        $I->loginAsRoot();
        $I->searchDomainList($addonDomainName);
        $I->checkProtectionStatusIs(DomainListPage::STATUS_DOMAIN_IS_NOT_PRESENT_IN_THE_FILTER);
        $I->makeSpampanelApiRequest()->addDomainAlias($addonDomainName, $domain);
        $I->checkProtectionStatusIs(DomainListPage::STATUS_DOMAIN_IS_PRESENT_IN_THE_FILTER);

        $I->goToConfigurationPageAndSetOptions([
            Locator::combine(ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT_XPATH, ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT_CSS) => false,
            Locator::combine(ConfigurationPage::AUTOMATICALLY_DELETE_DOMAINS_OPT_XPATH, ConfigurationPage::AUTOMATICALLY_DELETE_DOMAINS_OPT_CSS) => true,
            Locator::combine(ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_XPATH, ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_CSS) => true,
            Locator::combine(ConfigurationPage::ADD_ADDON_AS_ALIAS_CPANEL_OPT_XPATH, ConfigurationPage::ADD_ADDON_AS_ALIAS_CPANEL_OPT_CSS) => false,
        ]);

        return [
            'addon_domain_name' => $addonDomainName,
            'account' => $account
        ];
    }

    private function setupErrorAddedAsDomainNotAliasScenario(CommonSteps $I)
    {
        $I->goToConfigurationPageAndSetOptions([
            Locator::combine(ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT_XPATH, ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT_CSS) => true,
            Locator::combine(ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_XPATH, ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_CSS) => true,
            Locator::combine(ConfigurationPage::ADD_ADDON_AS_ALIAS_CPANEL_OPT_XPATH, ConfigurationPage::ADD_ADDON_AS_ALIAS_CPANEL_OPT_CSS) => false,
        ]);

        $account = $I->createNewAccount();
        $domain = $account['domain'];
        $I->loginAsClient($account['username'], $account['password']);
        $addonDomainName = $I->addAddonDomainAsClient($domain);
        $I->assertDomainExistsInSpampanel($addonDomainName);

        $I->loginAsRoot();
        $I->goToConfigurationPageAndSetOptions([
            Locator::combine(ConfigurationPage::ADD_ADDON_AS_ALIAS_CPANEL_OPT_XPATH, ConfigurationPage::ADD_ADDON_AS_ALIAS_CPANEL_OPT_CSS) => true,
        ]);

        return [
            'addon_domain_name' => $addonDomainName,
            'account' => $account
        ];
    }
}