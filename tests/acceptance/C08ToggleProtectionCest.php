<?php

use Page\ConfigurationPage;
use Page\DomainListPage;
use Step\Acceptance\CommonSteps;
use Step\Acceptance\ToggleProtectionSteps;
use Codeception\Util\Locator;

class C08ToggleProtectionCest
{
    protected  $feature;

    public function _before(ToggleProtectionSteps $I)
    {
        // Login as root
        $I->loginAsRoot();

        //The unique feature added to the custom package
        $this->feature = uniqid("feature");

        // Create a default package
        $I->createDefaultPackage();

        //Create a custom package with ProSpamFilter enabled
        $I->createCustomPackage($this->feature);
    }

    public function _after(ToggleProtectionSteps $I)
    {
        $I->removeCreatedAccounts();
        $I->removeFeature($this->feature);
    }

    public function _failed(ToggleProtectionSteps $I)
    {
        $this->_after($I);
    }

    public function testToggleProtectionErrorAddedAsAliasNotDomain(ToggleProtectionSteps $I)
    {
        $setup = $I->setupErrorAddedAsAliasNotDomainScenario();
        $addonDomainName = $setup['addon_domain_name'];

        // Test
        $I->searchDomainList($addonDomainName);
        $I->click(DomainListPage::TOGGLE_PROTECTION_LINK);
        $message = "The protection status of $addonDomainName could not be changed to unprotected because subdomain, parked and addon domains are treated as normal domains and \"$addonDomainName\" is already added as an alias.";
        $I->waitForText($message, 60);
    }

    public function testHookErrorAddedAsAliasNotDomain(ToggleProtectionSteps $I)
    {
        $setup = $I->setupErrorAddedAsAliasNotDomainScenario();

        $addonDomainName = $setup['addon_domain_name'];
        $account = $setup['account'];

        $I->assertDomainExistsInSpampanel($addonDomainName);
        $I->loginAsClient($account['username'], $account['password']);
        $I->removeAddonDomainAsClient($addonDomainName);
        $I->assertDomainExistsInSpampanel($addonDomainName);
    }

    public function testToggleProtectionErrorAddedAsDomainNotAlias(ToggleProtectionSteps $I)
    {
        $setup = $I->setupErrorAddedAsDomainNotAliasScenario();
        $addonDomainName = $setup['addon_domain_name'];

        // Test
        $I->searchDomainList($addonDomainName);
        $I->checkProtectionStatusIs(DomainListPage::STATUS_DOMAIN_IS_PRESENT_IN_THE_FILTER);
        $I->click(DomainListPage::TOGGLE_PROTECTION_LINK);
        $message = "The protection status of $addonDomainName could not be changed to unprotected because subdomain, parked and addon domains are treated as aliases and \"$addonDomainName\" is already added as a normal domain.";
        $I->waitForText($message, 60);
    }

    public function testHookErrorAddedAsDomainNotAlias(ToggleProtectionSteps $I)
    {
        $setup = $I->setupErrorAddedAsDomainNotAliasScenario();
        $addonDomainName = $setup['addon_domain_name'];
        $account = $setup['account'];

        // Test
        $I->assertDomainExistsInSpampanel($addonDomainName);
        $I->loginAsClient($account['username'], $account['password']);
        $I->removeAddonDomainAsClient($addonDomainName);
        $I->assertDomainExistsInSpampanel($addonDomainName);
    }

    public function testToggleAsAliasAndUntoggleAlias(ToggleProtectionSteps $I)
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

    public function testToggleProtectionErrorForDomainAddedWithoutFiltering(ToggleProtectionSteps $I)
    {
        $setup = $I->setupDomainAddedWithoutFilteringScenario();
        $domain = $setup['domain'];

        // Test
        $I->searchDomainList($domain);
        $I->click(DomainListPage::TOGGLE_PROTECTION_LINK);
        $I->waitForText("The protection status of $domain could not be changed to protected because this feature is disabled for this domain's package. To enable it, you need to update the feature list of the package assigned to this domain.", 60);
        $I->checkProtectionStatusIs(DomainListPage::STATUS_DOMAIN_IS_NOT_PRESENT_IN_THE_FILTER);
        $I->removeDomains();
    }

    public function testToggleProtectionErrorForAddonAddedWithoutFiltering(ToggleProtectionSteps $I)
    {
        $setup = $I->setupAddonAddedWithoutFilteringScenario();
        $addonDomainName = $setup['addon_domain_name'];

        // Test
        $I->searchDomainList($addonDomainName);
        $I->checkProtectionStatusIs(DomainListPage::STATUS_DOMAIN_IS_NOT_PRESENT_IN_THE_FILTER);
        $I->click(DomainListPage::TOGGLE_PROTECTION_LINK);
        $I->waitForText("The protection status of $addonDomainName could not be changed to protected because this feature is disabled for this domain's package. To enable it, you need to update the feature list of the package assigned to this domain.", 60);
        $I->removeDomains();
    }

    public function testToggleProtectionErrorForSubdomainAddedWithoutFiltering(ToggleProtectionSteps $I)
    {
        $setup = $I->setupSubdomainAddedWithoutFilteringScenario();
        $subDomainName = $setup['sub_domain_name'];

        // Test
        $I->searchDomainList($subDomainName);
        $I->checkProtectionStatusIs(DomainListPage::STATUS_DOMAIN_IS_NOT_PRESENT_IN_THE_FILTER);
        $I->click(DomainListPage::TOGGLE_PROTECTION_LINK);
        $I->waitForText("The protection status of $subDomainName could not be changed to protected because this feature is disabled for this domain's package. To enable it, you need to update the feature list of the package assigned to this domain.", 60);
        $I->removeDomains();
    }

    public function testToggleProtectionErrorForParkedDomainAddedWithoutFiltering(ToggleProtectionSteps $I)
    {
        $setup = $I->setupParkedDomainAddedWithoutFilteringScenario();
        $parkedDomain = $setup['parked_domain_name'];

        // Test
        $I->searchDomainList($parkedDomain);
        $I->checkProtectionStatusIs(DomainListPage::STATUS_DOMAIN_IS_NOT_PRESENT_IN_THE_FILTER);
        $I->click(DomainListPage::TOGGLE_PROTECTION_LINK);
        $I->waitForText("The protection status of $parkedDomain could not be changed to protected because this feature is disabled for this domain's package. To enable it, you need to update the feature list of the package assigned to this domain.", 60);
        $I->removeDomains();
    }
}