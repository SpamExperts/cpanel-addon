<?php

use Step\Acceptance\CommonSteps;
use Step\Acceptance\UpdateSteps;
use Page\ProfessionalSpamFilterPage;
use Page\UpdatePage;

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
        $I->goToPage(ProfessionalSpamFilterPage::UPDATE_BTN, UpdatePage::TITLE);

        $I->verifyPageLayout();
        $I->submitUpgradeForm();
        $I->seeNoticeAfterUpgrade();

        // still need to perform proper upgrade
    }
}