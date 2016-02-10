<?php

use Step\Acceptance\CommonSteps;
use Step\Acceptance\UpdateSteps;

class C06UpdateCest
{
    public function _before(CommonSteps $I)
    {
        $I->login();
    }

    public function _after(CommonSteps $I)
    {
    }

    public function checkUpdatePage(UpdateSteps $I)
    {
        $I->goToPage();

        $I->verifyPageLayout();
        $I->submitUpgradeForm();
        $I->seeNoticeAfterUpgrade();

        // still need to perform proper upgrade
    }
}