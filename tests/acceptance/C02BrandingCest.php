<?php

use Page\BrandingPage;
use Page\ProfessionalSpamFilterPage;
use Step\Acceptance\BrandingSteps;

class C02BrandingCest
{
    private $newBrandname = 'ProspamfilterBrandTest';

    public function _before(BrandingSteps $I)
    {
        $I->loginAsRoot();
        $I->goToPage(ProfessionalSpamFilterPage::BRANDING_BTN, BrandingPage::TITLE);
    }

    public function _after(BrandingSteps $I)
    {
        $I->removeCreatedAccounts();
        $I->goToPage(ProfessionalSpamFilterPage::BRANDING_BTN, BrandingPage::TITLE);
        $I->setupOriginalBrandname();
    }

    public function _failed(BrandingSteps $I)
    {
        $this->_after($I);
    }

    public function checkBrandingPage(BrandingSteps $I)
    {
        $I->submitBrandingSettingForm();
        $I->seeSettingsSavedSuccessfully();

        $I->setupBrandname($this->newBrandname);

        $account = $I->createNewAccount();
        $I->logout();
        $I->loginAsClient($account['username'], $account['password']);
        $I->see($this->newBrandname);
        $I->changeToX3Theme();
        $I->waitForText($this->newBrandname, 30);
        $I->changeToPaperLanternTheme();
        $I->waitForText($this->newBrandname, 30);

        $I->logoutAsClient();
        $I->loginAsRoot();
    }
}
