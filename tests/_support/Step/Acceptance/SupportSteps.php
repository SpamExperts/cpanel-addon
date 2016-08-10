<?php

namespace Step\Acceptance;
use Page\SupportPage;
use Codeception\Util\Locator;


class SupportSteps extends CommonSteps
{
     public function verifyPageLayout()
    {
        $this->see(SupportPage::TITLE, SupportPage::TITLE_XPATH);
        // addon information
        $this->see(SupportPage::TEXT_A);
        $this->see(SupportPage::TEXT_B);
        $this->see(SupportPage::TEXT_C);
        $this->see(SupportPage::TEXT_D);
        $this->see(SupportPage::TEXT_E);
        $this->see(SupportPage::DESCRIPTION);
        $this->seeElement(Locator::combine(SupportPage::RUN_DIAGNOSTICS_BTN_XPATH, SupportPage::RUN_DIAGNOSTICS_BTN_CSS));
    }

    public function submitDiagnosticForm()
    {
        //Submit
        $this->click(Locator::combine(SupportPage::RUN_DIAGNOSTICS_BTN_XPATH, SupportPage::RUN_DIAGNOSTICS_BTN_CSS));
    }

    public function seeDiagnostics()
    {
        //Verify diagnostics result
        $this->see("PHP version:", SupportPage::PHP_VERSION_XPATH);
        $this->seeElement(SupportPage::DIAGNOSTIC_RESULT_XPATH);
        $this->see("PHP extensions:", SupportPage::PHP_EXTENSIONS_XPATH);
        $this->seeElement(SupportPage::DIAGNOSTIC_RESULT_XPATH);
        $this->see("Configuration binary:", SupportPage::CONFIGURATION_BINARY_XPATH);
        $this->seeElement(SupportPage::DIAGNOSTIC_RESULT_XPATH);
        $this->see("Configuration permissions:", SupportPage::CONFIGURATION_PERMISSIONS_XPATH);
        $this->seeElement(SupportPage::DIAGNOSTIC_RESULT_XPATH);
        $this->see("Panel version:", SupportPage::PANEL_VERSION_XPATH);
        $this->seeElement(SupportPage::DIAGNOSTIC_RESULT_XPATH);
        $this->see("Addon version:", SupportPage::ADDON_VERSION_XPATH);
        $this->seeElement(SupportPage::DIAGNOSTIC_RESULT_XPATH);
        $this->see("Hashes:", SupportPage::HASHES_XPATH);
        $this->seeElement(SupportPage::DIAGNOSTIC_RESULT_XPATH);
        $this->see("Hooks:", SupportPage::HOOKS_XPATH);
        $this->seeElement(SupportPage::DIAGNOSTIC_RESULT_XPATH);
        $this->see("Symlinks:", SupportPage::SYMLINKS_XPATH);
        $this->seeElement(SupportPage::DIAGNOSTIC_RESULT_XPATH);
        $this->see("Controlpanel API:", SupportPage::CONTROLPANEL_API_XPATH);
        $this->seeElement(SupportPage::DIAGNOSTIC_RESULT_XPATH);
        $this->see("Spamfilter API:", SupportPage::SPAMFILTER_API_XPATH);
        $this->seeElement(SupportPage::DIAGNOSTIC_RESULT_XPATH);
    }

}
