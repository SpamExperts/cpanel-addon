<?php

use Pages\BulkprotectPage;
use Pages\ConfigurationPage;
use Pages\ProfessionalSpamFilterPage;
use Step\Acceptance\BulkProtectSteps;

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
            ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT => false,
        ));

        $accounts = $I->createNewAccounts();

        $I->goToPage(ProfessionalSpamFilterPage::BULKPROTECT_BTN, BulkprotectPage::TITLE);
        $I->verifyPageLayout();
        $I->seeLastExecutionInfo();
        $I->submitBulkprotectForm();
        $I->seeBulkprotectRanSuccessfully();
        $I->see('Domain has been added', '#resultdomainstatus');

        $I->goToPage(ProfessionalSpamFilterPage::BULKPROTECT_BTN, BulkprotectPage::TITLE);
        $I->submitBulkprotectForm();
        $I->seeBulkprotectRanSuccessfully();
        $I->see('Skipped: Domain already exists', '#resultdomainstatus');

        $I->goToPage(ProfessionalSpamFilterPage::CONFIGURATION_BTN, ConfigurationPage::TITLE);
        $I->setConfigurationOptions(array(
            ConfigurationPage::AUTOMATICALLY_CHANGE_MX_OPT => true,
            ConfigurationPage::FORCE_CHANGE_MX_ROUTE_OPT => true,
        ));

        $I->goToPage(ProfessionalSpamFilterPage::BULKPROTECT_BTN, BulkprotectPage::TITLE);
        $I->seeLastExecutionInfo();
        $I->submitBulkprotectForm();
        $I->seeBulkprotectRanSuccessfully();
        $I->see('Route & MX have been updated', '#resultdomainstatus');
    }
}