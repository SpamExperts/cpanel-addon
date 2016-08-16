<?php

use Step\Acceptance\CommonSteps;
use Step\Acceptance\UpdateSteps;
use Page\ProfessionalSpamFilterPage;
use Page\UpdatePage;

class C06UpdateCest
{
    public function _before(CommonSteps $I)
    {
        // Login as root
        $I->login();
    }

    /**
     * Verify update page layout and functionality
     */
    public function checkUpdatePage(UpdateSteps $I)
    {
        // Go to the update page
        $I->goToPage(ProfessionalSpamFilterPage::UPDATE_BTN, UpdatePage::TITLE);

        // Verify the page layout
        $I->verifyPageLayout();

        // Start the update operation
        $I->submitUpgradeForm();

        // Check the operation result
        $I->seeNoticeAfterUpgrade();

        // TODO still need to perform proper upgrade
    }
}