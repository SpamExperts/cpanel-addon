<?php

use Step\Acceptance\CommonSteps;
use Step\Acceptance\MigrationSteps;
use Page\ProfessionalSpamFilterPage;
use Page\MigrationPage;

class C05MigrationCest
{
    public function _before(CommonSteps $I)
    {
        // Login as root
        $I->login();
    }

    /**
     * Verify migration page layout and functionality
     */
    public function checkMigrationPage(MigrationSteps $I)
    {
        // Go to migration page
        $I->goToPage(ProfessionalSpamFilterPage::MIGRATION_BTN, MigrationPage::TITLE);
        
        // Verify migration page layout
        $I->verifyPageLayout();

        // Submit migration form
        $I->submitMigrationForm();

        // Check the error message after the submission
        $I->seeErrorAfterMigrate();

        //TODO still need to add actual new account

    }
}