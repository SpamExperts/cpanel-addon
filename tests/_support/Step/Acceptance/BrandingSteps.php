<?php

namespace Step\Acceptance;

use Page\BrandingPage;
use Codeception\Util\Locator;

class BrandingSteps extends CommonSteps
{
    /**
     * Function used to verify the branding page layout
     */
    public function verifyPageLayout()
    {
        // verify current branding info
        $this->see("Current branding", "//h4[contains(.,'Current branding')]");
        $this->see("Your branding is currently set to:");
        $this->waitForElement("//img[@id='some_icon']");
        $this->see("Professional Spam Filter");

        // verify change branding info
        $this->see("If you want to change the branding shown above, you can do this in the form below.");
        $this->see("Change branding", "//h4[contains(.,'Change branding')]");
        $this->seeElement("//label[@for='brandname']");
        $this->waitForElement("//input[@data-original-title='Brandname']");
        $this->seeElement("//label[@for='brandicon']");
        $this->waitForElement("//input[@data-original-title='Brandicon']");
    }

    /**
     * Function used to submit the branding page form
     */
    public function submitBrandingSettingForm()
    {
        // Click the Save branding settings button
        $this->click(Locator::combine(BrandingPage::SAVE_BRANDING_BTN_XPATH, BrandingPage::SAVE_BRANDING_BTN_CSS));
    }

    /**
     * Function used to check if the new brand was saved
     * @param $name - brand expected
     */
    public function seeSettingsSavedSuccessfully($name)
    {
        $this->waitForText("No new icon uploaded, using the current one.", 60);
        $this->waitForText("The branding settings have been saved.", 60);
        $this->waitForText("Brandname is set to '".$name."'.", 60);
    }

    /**
     * Function used to change the current brand name
     * @param $name - new brand
     */
    public function setupBrandname($name)
    {
        $this->fillField(Locator::combine(BrandingPage::BRANDNAME_FIELD_XPATH, BrandingPage::BRANDNAME_FIELD_CSS), $name);
        $this->submitBrandingSettingForm();
        $this->waitForText("The branding settings have been saved.", 60);
        $this->waitForText("Brandname is set to '$name'.", 60);
        $this->currentBrandname = $name;
    }

    /**
     * Function used to restore the original brand name
     */
    public function setupOriginalBrandname()
    {
        // If the brand is changed, change it back to the original brand name
        if ($this->currentBrandname != BrandingPage::ORIGINAL_BRANDNAME)
            $this->setupBrandname(BrandingPage::ORIGINAL_BRANDNAME);
    }
}
