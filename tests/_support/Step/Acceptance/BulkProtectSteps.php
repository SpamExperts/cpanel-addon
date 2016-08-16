<?php

namespace Step\Acceptance;
use Codeception\Util\Locator;
use Page\BulkprotectPage;

class BulkProtectSteps extends CommonSteps
{
    /**
     * Function used to verify the bulk protect page layout
     */
    public function verifyPageLayout()
    {
        $this->see(BulkprotectPage::TITLE, Locator::combine(BulkprotectPage::TITLE_XPATH, BulkprotectPage::TITLE_CSS));
        $this->see(BulkprotectPage::DESCRIPTION_A);
        $this->see(BulkprotectPage::DESCRIPTION_B);
    }

    /**
     * Function used to check if bulk protect is running
     */
    public function seeBulkprotectRunning()
    {
        $this->see("BULK PROTECTING, DO NOT RELOAD THIS PAGE!");
        $this->see("Results will be shown here when the process has finished");
        $this->see("It might take a while, especially if you have many domains or a slow connection.");
        $this->see("Please be patient while we're running the bulk protector");
    }

    /**
     * Function used to check the number of domains info
     */
    public function seeNumberOfDomainsInfo()
    {
        $this->see("There are no domains on this server.");
        $this->seeElement(".//*[@id='statusContainer']/table/thead/tr");
    }
}
