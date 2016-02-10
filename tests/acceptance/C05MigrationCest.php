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

    public function checkMigrationPage(MigrationSteps $I)
    {
        $I->goToPage();

        $I->verifyPageLayout();
        $I->submitMigrationForm();
        $I->seeErrorAfterMigrate();

        // still need to add actual new account

    }
}