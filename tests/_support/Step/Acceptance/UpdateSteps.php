<?php

namespace Step\Acceptance;
use Codeception\Util\Locator;
use Page\UpdatePage;

class UpdateSteps extends CommonSteps
{  
    public function verifyPageLayout()
    {
        // update info
        $this->see(UpdatePage::TITLE, UpdatePage::TITLE_XPATH);
        $this->waitForText(UpdatePage::DESCRIPTION_A);
        $this->waitForText(UpdatePage::DESCRIPTION_B);
        // tier of addon to install
        $this->see('Tier of addon to install');
        $this->seeelement(Locator::combine(UpdatePage::TIER_DROP_DOWN_XPATH, UpdatePage::TIER_DROP_DOWN_CSS));
        // force reinstalling the addon
        $this->seeElement(Locator::combine(UpdatePage::FORCE_REINSTALL_INPUT_XPATH, UpdatePage::FORCE_REINSTALL_INPUT_CSS));
        $this->see("Force a reinstall even if the system is up to date.");
        // 'Click to upgrade' button
        $this->waitForElement(Locator::combine(UpdatePage::CLICK_TO_UPGRADE_BTN_XPATH, UpdatePage::CLICK_TO_UPGRADE_BTN_CSS));
    }

    public function submitUpgradeForm()
    {
        $this->click(Locator::combine(UpdatePage::CLICK_TO_UPGRADE_BTN_XPATH, UpdatePage::CLICK_TO_UPGRADE_BTN_CSS));
    }

    public function seeNoticeAfterUpgrade()
    {
        $this->seeOneOf(array(
            'The update process has been initiated successfully. Please wait around 30 seconds before opening other pages to allow the update process to complete',
            'There is no stable update available to install. You are already at the latest version.'));
    }
}
