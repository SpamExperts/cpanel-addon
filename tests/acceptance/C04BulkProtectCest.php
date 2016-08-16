<?php

use Page\BulkprotectPage;
use Page\ConfigurationPage;
use Page\TerminateAccountsPage;
use Page\ProfessionalSpamFilterPage;
use Step\Acceptance\BulkProtectSteps;
use Codeception\Util\Locator;

class C04BulkProtectCest
{
    public function _before(BulkProtectSteps $I)
    {
        $I->loginAsRoot();
    }

    public function _after(BulkProtectSteps $I)
    {
        $I->removeCreatedAccounts();
    }

    public function _failed(BulkProtectSteps $I)
    {
        $this->_after($I);
    }

    public function checkBulkProtectPage(BulkProtectSteps $I)
    {
        $I->removeAllAccounts();
        $I->goToPage(ProfessionalSpamFilterPage::CONFIGURATION_BTN, ConfigurationPage::TITLE);
        $I->setConfigurationOptions(array(
            Locator::combine(ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT_XPATH, ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT_CSS) => false,
        ));

        $accounts = $I->createNewAccounts();

        $I->goToPage(ProfessionalSpamFilterPage::BULKPROTECT_BTN, BulkprotectPage::TITLE);
        $I->verifyPageLayout();
        $I->seeBulkProtectLastExecutionInfo();
        $I->submitBulkprotectForm();
        $I->seeBulkprotectRanSuccessfully();
        $I->see('Domain has been added', BulkprotectPage::TABLE);

        $I->goToPage(ProfessionalSpamFilterPage::BULKPROTECT_BTN, BulkprotectPage::TITLE);
        $I->submitBulkprotectForm();
        $I->seeBulkprotectRanSuccessfully();
        $I->see('Skipped: Domain already exists', BulkprotectPage::TABLE);

        $I->goToPage(ProfessionalSpamFilterPage::CONFIGURATION_BTN, ConfigurationPage::TITLE);
        $I->setConfigurationOptions(array(
            Locator::combine(ConfigurationPage::AUTOMATICALLY_CHANGE_MX_OPT_XPATH, ConfigurationPage::AUTOMATICALLY_CHANGE_MX_OPT_CSS) => true,
            Locator::combine(ConfigurationPage::FORCE_CHANGE_MX_ROUTE_OPT_XPATH, ConfigurationPage::FORCE_CHANGE_MX_ROUTE_OPT_CSS) => true,
        ));

        $I->goToPage(ProfessionalSpamFilterPage::BULKPROTECT_BTN, BulkprotectPage::TITLE);
        $I->seeBulkProtectLastExecutionInfo();
        $I->submitBulkprotectForm();
        $I->seeBulkprotectRanSuccessfully();
        $I->see('Route & MX have been updated', BulkprotectPage::TABLE);
    }
}