<?php

namespace Step\Acceptance;
use Page\ConfigurationPage;
use Page\DomainListPage;
use Codeception\Util\Locator;

class ToggleProtectionSteps extends CommonSteps
{
	public function setupErrorAddedAsAliasNotDomainScenario()
    {
        // setup
        $this->goToConfigurationPageAndSetOptions([
            Locator::combine(ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT_XPATH, ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT_CSS) => false,
            Locator::combine(ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_XPATH, ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_CSS) => true,
        ]);

        $account = $this->createNewAccount();
        $domain = $account['domain'];

        $this->searchDomainList($domain);
        $this->click(DomainListPage::TOGGLE_PROTECTION_LINK);
        $this->waitForText("The protection status of $domain has been changed to protected", 60);
        $this->checkProtectionStatusIs(DomainListPage::STATUS_DOMAIN_IS_PRESENT_IN_THE_FILTER);

        $this->loginAsClient($account['username'], $account['password']);
        $addonDomainName = $this->addAddonDomainAsClient($domain);

        $this->loginAsRoot();
        $this->searchDomainList($addonDomainName);
        $this->checkProtectionStatusIs(DomainListPage::STATUS_DOMAIN_IS_NOT_PRESENT_IN_THE_FILTER);
        $this->makeSpampanelApiRequest()->addDomainAlias($addonDomainName, $domain);
        $this->checkProtectionStatusIs(DomainListPage::STATUS_DOMAIN_IS_PRESENT_IN_THE_FILTER);

        $this->goToConfigurationPageAndSetOptions([
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

    public function setupErrorAddedAsDomainNotAliasScenario()
    {
        $this->goToConfigurationPageAndSetOptions([
            Locator::combine(ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT_XPATH, ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT_CSS) => true,
            Locator::combine(ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_XPATH, ConfigurationPage::PROCESS_ADDON_CPANEL_OPT_CSS) => true,
            Locator::combine(ConfigurationPage::ADD_ADDON_AS_ALIAS_CPANEL_OPT_XPATH, ConfigurationPage::ADD_ADDON_AS_ALIAS_CPANEL_OPT_CSS) => false,
        ]);

        $account = $this->createNewAccount();
        $domain = $account['domain'];
        $this->loginAsClient($account['username'], $account['password']);
        $addonDomainName = $this->addAddonDomainAsClient($domain);
        $this->assertDomainExistsInSpampanel($addonDomainName);

        $this->loginAsRoot();
        $this->goToConfigurationPageAndSetOptions([
            Locator::combine(ConfigurationPage::ADD_ADDON_AS_ALIAS_CPANEL_OPT_XPATH, ConfigurationPage::ADD_ADDON_AS_ALIAS_CPANEL_OPT_CSS) => true,
        ]);

        return [
            'addon_domain_name' => $addonDomainName,
            'account' => $account
        ];
    }

}