<?php

namespace Step\Acceptance;
use Codeception\Util\Locator;
use Page\UpdatePage;

class UpdateSteps extends CommonSteps
{
    /**
     * Function used to verify update page layout
     */
    public function verifyPageLayout()
    {
        // Look for page title
        $this->see(UpdatePage::TITLE, UpdatePage::TITLE_XPATH);


        // Look for page description
        $this->waitForText(UpdatePage::DESCRIPTION_A);
        $this->waitForText(UpdatePage::DESCRIPTION_B);


        // Look for tier of addon to install drop down
        $this->see('Tier of addon to install');
        $this->seeelement(Locator::combine(UpdatePage::TIER_DROP_DOWN_XPATH, UpdatePage::TIER_DROP_DOWN_CSS));


        // Look for force reinstall checkbox
        $this->seeElement(Locator::combine(UpdatePage::FORCE_REINSTALL_INPUT_XPATH, UpdatePage::FORCE_REINSTALL_INPUT_CSS));
        $this->see("Force a reinstall even if the system is up to date.");

        // Look for click to upgrade button
        $this->waitForElement(Locator::combine(UpdatePage::CLICK_TO_UPGRADE_BTN_XPATH, UpdatePage::CLICK_TO_UPGRADE_BTN_CSS));
    }

    /**
     * Function used to start the upgrade process
     */
    public function submitUpgradeForm()
    {
        // Click the upgrade button
        $this->click(Locator::combine(UpdatePage::CLICK_TO_UPGRADE_BTN_XPATH, UpdatePage::CLICK_TO_UPGRADE_BTN_CSS));
    }

    /**
     * Function used to check the update operation results
     */
    public function seeNoticeAfterUpgrade()
    {
        // Check if one of the following messages appear on the page
        $this->seeOneOf(array(
            'The update process has been initiated successfully. Please wait around 30 seconds before opening other pages to allow the update process to complete',
            'There is no stable update available to install. You are already at the latest version.',
            'There is no testing update available to install. You are already at the latest version.'
        ));
    }
}
