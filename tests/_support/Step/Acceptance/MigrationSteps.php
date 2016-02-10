<?php

namespace Step\Acceptance;

class MigrationSteps extends \WebGuy
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
        $I->waitForText('Migration');
        $I->click('html/body/div[1]/div/ul/li[5]/div');
    }

    public function verifyPageLayout()
    {
        $this->see("Migration", "//h3[contains(.,'Migration')]");
        $this->waitForText('On this page you can migrate to a different admin/reseller in the spamfilter.');
        $this->waitForText('During migration, the domains will be assigned to the new user (given the credentials for the new user are correct) and the configuration of the addon will be switched to the new user.');
        // 'Current username'
        $this->see('Current username');
        $this->waitForElement("//input[@data-original-title='Current username']");
        // 'New username'
        $this->see('New username');
        $this->waitForElement("//input[@data-original-title='New username']");
        // 'New password'
        $this->see('New password');
        $this->waitForElement("//input[@data-original-title='New password']");
        $this->waitForText('I am sure I want to migrate all protected domains on this server to this new user.');
        // Confirmation radio button
        $this->waitForElement("//input[@data-original-title='Confirmation']");
        // 'Migrate' button
        $this->waitForElement("//input[@class='btn btn-primary']");
    }

    public function submitMigrationForm()
    {
        $this->click('Migrate');
    }

    public function seeErrorAfterMigrate()
    {
        $this->see('One or more settings are not correctly set.');
    }
}
