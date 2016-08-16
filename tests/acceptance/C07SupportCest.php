<?php

use Step\Acceptance\CommonSteps;
use Step\Acceptance\SupportSteps;
use Page\ProfessionalSpamFilterPage;
use Page\SupportPage;


class C07SupportCest
{
    public function _before(CommonSteps $I)
    {
        // Login as root
        $I->login();
    }


    /**
     * Verify support page layout and functionality
     */
    public function checkSupportPage(SupportSteps $I)
    {
        // Go to the support page
        $I->goToPage(ProfessionalSpamFilterPage::SUPPORT_BTN, SupportPage::TITLE);

        // Verify the page layout
        $I->verifyPageLayout();

        // Run diagnostics
        $I->submitDiagnosticForm();

        // See diagnostics result
        $I->seeDiagnostics();
    }
}