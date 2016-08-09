<?php

use Step\Acceptance\CommonSteps;
use Step\Acceptance\MigrationSteps;

class C05MigrationCest
{
    public function _before(CommonSteps $I)
    {
        $I->login();
    }

    public function _after(CommonSteps $I)
    {
    }

    /**
     * Verify migration page layout and functionality
     */
    public function checkMigrationPage(MigrationSteps $I)
    {
        // Go to migration page
        $I->goToPage();

        // Verify migration page layout
        $I->verifyPageLayout();

        // Submit migration form
        $I->submitMigrationForm();

        // Check the error message after the submission
        $I->seeErrorAfterMigrate();

        // still need to add actual new account

    }
}