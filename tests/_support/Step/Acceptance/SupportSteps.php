<?php

namespace Step\Acceptance;

class SupportSteps extends \WebGuy
{
    public function goToPage()
    {
        $I = $this;
        $I->switchToWindow();
        $I->reloadPage();
        $I->switchToIFrame('mainFrame');
        $I->waitForText('Plugins');
        $I->click('Plugins');
        $I->waitForText('Professional Spam Filter');
        $I->click('Professional Spam Filter');
        $I->waitForText('Support');
        $I->click('html/body/div[1]/div/ul/li[7]/div');

    }

     public function verifyPageLayout()
    {
        $this->see("Support", "//h3[contains(.,'Support')]");
        // addon information
        $this->see('Information');
        $this->see('Controlpanel: Cpanel v');
        $this->see('PHP version:');
        $this->see('Addon version:');
        $this->see('Diagnostics');
        $this->see('In case you have issues with the addon, you can run a diagnostics on your installation prior to contacting support.');
        $this->seeElement("//input[@class='btn btn-primary']");
    }

    public function submitDiagnosticForm()
    {
        $this->click('Run diagnostics');
    }

    public function seeDiagnostics()
    {
        $this->see("PHP version:", "//strong[contains(.,'PHP version:')]");
        $this->seeElement("//span[contains(.,'OK!')]");
        $this->see("PHP extensions:", "//strong[contains(.,'PHP extensions:')]");
        $this->seeElement("//span[contains(.,'OK!')]");
        $this->see("Configuration binary:", "//strong[contains(.,'Configuration binary:')]");
        $this->seeElement("//span[contains(.,'OK!')]");
        $this->see("Configuration permissions:", "//strong[contains(.,'Configuration permissions:')]");
        $this->seeElement("//span[contains(.,'OK!')]");
        $this->see("Panel version:", "//strong[contains(.,'Panel version:')]");
        $this->seeElement("//span[contains(.,'OK!')]");
        $this->see("Addon version:", "//strong[contains(.,'Addon version:')]");
        $this->seeElement("//span[contains(.,'OK!')]");
        $this->see("Hashes:", "//strong[contains(.,'Hashes:')]");
        $this->seeElement("//span[contains(.,'OK!')]");
        $this->see("Hooks:", "//strong[contains(.,'Hooks:')]");
        $this->seeElement("//span[contains(.,'OK!')]");
        $this->see("Symlinks:", "//strong[contains(.,'Symlinks:')]");
        $this->seeElement("//span[contains(.,'OK!')]");
        $this->see("Controlpanel API:", "//strong[contains(.,'Controlpanel API:')]");
        $this->seeElement("//span[contains(.,'OK!')]");
        $this->see("Spamfilter API:", "//strong[contains(.,'Spamfilter API:')]");
        $this->seeElement("//span[contains(.,'OK!')]");
    }

}
