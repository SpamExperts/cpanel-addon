<?php

use Page\BulkprotectPage;
use Page\ConfigurationPage;
use Page\ProfessionalSpamFilterPage;
use Step\Acceptance\BulkProtectSteps;
use Codeception\Util\Locator;

class C04BulkProtectCest
{
    public function _before(BulkProtectSteps $I)
    {
        // Login as root
        $I->loginAsRoot();
    }

    public function _after(BulkProtectSteps $I)
    {
        // Remove all created accounts
        $I->removeCreatedAccounts();
    }

    public function _failed(BulkProtectSteps $I)
    {
        $this->_after($I);
    }

    /**
     * Verify the bulk protect page layout and functionality
     */
    public function checkBulkProtectPage(BulkProtectSteps $I)
    {

        // Remove all created accounts
        $I->removeAllAccounts();

        // Go to the plugin configuration page
        $I->goToPage(ProfessionalSpamFilterPage::CONFIGURATION_BTN, ConfigurationPage::TITLE);

        // Set plugin configuration options
        $I->setConfigurationOptions(array(
            Locator::combine(ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT_XPATH, ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT_CSS) => false,
        ));

        // Create a new client account
        $accounts = $I->createNewAccounts();


        // Go to bulk protect page
        $I->goToPage(ProfessionalSpamFilterPage::BULKPROTECT_BTN, BulkprotectPage::TITLE);

        // Verify the bulk protect page layout
        $I->verifyPageLayout();

        // Check the last execution date of bulkprotect
        $I->seeBulkProtectLastExecutionInfo();

        // Start the bulk protect operation
        $I->submitBulkprotectForm();

        // See if bulk protect operation finished
        $I->seeBulkprotectRanSuccessfully();

        // Check if client account domain has been protected
        $I->see('Domain has been added', BulkprotectPage::TABLE);

        // Go to bulk protect page again
        $I->goToPage(ProfessionalSpamFilterPage::BULKPROTECT_BTN, BulkprotectPage::TITLE);

        // Start the bulk protect operation
        $I->submitBulkprotectForm();

        // See if bulk protect operation finished
        $I->seeBulkprotectRanSuccessfully();

        // Check if client account domain is already protected
        $I->see('Skipped: Domain already exists', BulkprotectPage::TABLE);

        // Go to configuration page
        $I->goToPage(ProfessionalSpamFilterPage::CONFIGURATION_BTN, ConfigurationPage::TITLE);
        $I->setConfigurationOptions(array(
            Locator::combine(ConfigurationPage::AUTOMATICALLY_CHANGE_MX_OPT_XPATH, ConfigurationPage::AUTOMATICALLY_CHANGE_MX_OPT_CSS) => true,
            Locator::combine(ConfigurationPage::FORCE_CHANGE_MX_ROUTE_OPT_XPATH, ConfigurationPage::FORCE_CHANGE_MX_ROUTE_OPT_CSS) => true,
        ));

        // Go to bulk protect page
        $I->goToPage(ProfessionalSpamFilterPage::BULKPROTECT_BTN, BulkprotectPage::TITLE);

        // Chek the last execution date of bulk protect
        $I->seeBulkProtectLastExecutionInfo();

        // Start the bulk protect operation
        $I->submitBulkprotectForm();

        // Check if bulkprotect operaton finished
        $I->seeBulkprotectRanSuccessfully();

        // Check if Routes and MX are updated
        $I->see('Route & MX have been updated', BulkprotectPage::TABLE);
    }
}