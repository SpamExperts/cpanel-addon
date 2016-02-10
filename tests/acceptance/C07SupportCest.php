<?php

use Step\Acceptance\CommonSteps;
use Step\Acceptance\SupportSteps;

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
        $I->goToPage();

        $I->verifyPageLayout();
        $I->submitDiagnosticForm();
        $I->seeDiagnostics();
    }
}