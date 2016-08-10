<?php

namespace Step\Acceptance;
use Codeception\Util\Locator;
use Page\MigrationPage;


class MigrationSteps extends CommonSteps
{
    public function verifyPageLayout()
    {
        $this->see(MigrationPage::TITLE, MigrationPage::TITLE_XPATH);
        $this->waitForText(MigrationPage::DESCRIPTION_A);
        $this->waitForText(MigrationPage::DESCRIPTION_B);

        // 'Current username'
        $this->see('Current username');

        //Fields 
        $this->waitForElement(Locator::combine(MigrationPage::CURRENT_USERNAME_XPATH, MigrationPage::CURRENT_USERNAME_CSS), 30);

        // 'New username'
        $this->see('New username');
        $this->waitForElement(Locator::combine(MigrationPage::NEW_USERNAME_XPATH, MigrationPage::NEW_USERNAME_CSS), 30);

        // 'New password'
        $this->see('New password');
        $this->waitForElement(Locator::combine(MigrationPage::NEW_PASSWORD_XPATH, MigrationPage::NEW_PASSWORD_CSS), 30);
        $this->waitForText('I am sure I want to migrate all protected domains on this server to this new user.');

        // Confirmation radio button
        $this->waitForElement(Locator::combine(MigrationPage::CONFIRM_INPUT_XPATH, MigrationPage::CONFIRM_INPUT_CSS), 30);

        // 'Migrate' button
        $this->waitForElement(Locator::combine(MigrationPage::MIGRATE_BTN_XPATH, MigrationPage::MIGRATE_BTN_CSS), 30);
    }

    public function submitMigrationForm()
    {
       //Submit migration
       $this->click(Locator::combine(MigrationPage::MIGRATE_BTN_XPATH,MigrationPage::MIGRATE_BTN_CSS));
    }

    public function seeErrorAfterMigrate()
    {
        //Verify error message
        $this->see('One or more settings are not correctly set.');
    }
}
