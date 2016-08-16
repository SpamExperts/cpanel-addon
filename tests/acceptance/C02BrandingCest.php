<?php

use Page\BrandingPage;
use Page\ProfessionalSpamFilterPage;
use Step\Acceptance\BrandingSteps;

class C02BrandingCest
{
    private $newBrandname = 'ProspamfilterBrandTest';

    public function _before(BrandingSteps $I)
    {
        // Login as root
        $I->loginAsRoot();

        // Go to plugin branding page
        $I->goToPage(ProfessionalSpamFilterPage::BRANDING_BTN, BrandingPage::TITLE);
    }

    public function _after(BrandingSteps $I)
    {
        // Login as root
        $I->loginAsRoot();

        // Go to plugin branding page
        $I->goToPage(ProfessionalSpamFilterPage::BRANDING_BTN, BrandingPage::TITLE);

        // Restore the original brandname
        $I->setupOriginalBrandname();

        // Remove all created accounts
        $I->removeCreatedAccounts();
    }

    public function _failed(BrandingSteps $I)
    {
        $this->_after($I);
    }

    /**
     * Verify the branding page layout and functionality
     */
    public function checkBrandingPage(BrandingSteps $I)
    {
        // Submit the branding page form
        $I->submitBrandingSettingForm();

        // See if the original brand name is saved
        $I->seeSettingsSavedSuccessfully(BrandingPage::ORIGINAL_BRANDNAME);

        // Try to change the brandname
        $I->setupBrandname($this->newBrandname);

        // See if the new brandname is saved
        $I->seeSettingsSavedSuccessfully($this->newBrandname);

        // Create a new client account
        $account = $I->createNewAccount();

        // Login as client
        $I->loginAsClient($account['username'], $account['password']);

        // See if the client see the new brandname in page
        $I->waitForText($this->newBrandname, 30);
    }
}
