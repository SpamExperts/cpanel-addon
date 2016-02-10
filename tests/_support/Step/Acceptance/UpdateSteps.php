<?php

namespace Step\Acceptance;

class UpdateSteps extends \WebGuy
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
        $I->waitForText('Update');
        $I->click('html/body/div[1]/div/ul/li[6]/div');
    }

    public function verifyPageLayout()
    {
        // update info
        $this->see("Update", "//h3[contains(.,'Update')]");
        $this->waitForText('On this page you can manually update the addon to the latest version.');
        $this->waitForText('Auto-update is currently enabled. You can modify this in the configuration');
        // tier of addon to install
        $this->see('Tier of addon to install');
        $this->seeelement(".//*[@id='update_type']");
        // force reinstalling the addon
        $this->seeElement("//input[@data-original-title='Reinstall addon']");
        $this->see("Force a reinstall even if the system is up to date.");
        // 'Click to upgrade' button
        $this->waitForElement("//input[@class='btn btn-primary btn btn-primary']");
    }

    public function submitUpgradeForm()
    {
        $this->click('Click to upgrade');
    }

    public function seeNoticeAfterUpgrade()
    {
        $this->seeOneOf(array(
            'The update process has been initiated successfully. Please wait around 30 seconds before opening other pages to allow the update process to complete',
            'There is no stable update available to install. You are already at the latest version.'));
    }
}
