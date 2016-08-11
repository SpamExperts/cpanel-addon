<?php

use Step\Acceptance\CommonSteps;
use Step\Acceptance\SupportSteps;
use Page\ProfessionalSpamFilterPage;
use Page\SupportPage;


class C07SupportCest
{
    public function _before(CommonSteps $I)
    {
        $I->login();
    }

    public function _after(CommonSteps $I)
    {
    }

    public function checkSupportPage(SupportSteps $I)
    {
        $I->goToPage(ProfessionalSpamFilterPage::SUPPORT_BTN, SupportPage::TITLE);
        
        $I->verifyPageLayout();
        $I->submitDiagnosticForm();
        $I->seeDiagnostics();
    }
}