<?php

namespace Step\Acceptance;

use Page\BrandingPage;

class BrandingSteps extends CommonSteps
{
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

    public function submitBrandingSettingForm()
    {
        $this->click('Save Branding Settings');
    }

    public function seeSettingsSavedSuccessfully()
    {
        $this->see("No new icon uploaded, using the current one.");
        $this->see("The branding settings have been saved.");
        $this->see("Brandname is set to 'Professional Spam Filter'.");
    }

    public function setupBrandname($name)
    {
        $this->fillField(BrandingPage::BRANDNAME_INPUT, $name);
        $this->submitBrandingSettingForm();
        $this->see("The branding settings have been saved.");
        $this->see("Brandname is set to '$name'.");
        $this->currentBrandname = $name;
    }

    public function setupOriginalBrandname()
    {
        if ($this->currentBrandname == BrandingPage::ORIGINAL_BRANDNAME) {
            return;
        }

        $this->setupBrandname(BrandingPage::ORIGINAL_BRANDNAME);
    }
}
