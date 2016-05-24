<?php

namespace Step\Acceptance;

class BulkProtectSteps extends CommonSteps
{
    public function verifyPageLayout()
    {
        $this->see("Bulkprotect", "//h3[contains(.,'Bulkprotect')]");
        $this->see('On this page you can add all current domains to the spamfilter. Depending on the settings it may (or may not) execute certain actions.');
        $this->see('It is generally not required to run this more than once after the installation. Running bulk protect is usually only necessary after the first installation');
    }

    public function seeBulkprotectRunning()
    {
        $this->see("BULK PROTECTING, DO NOT RELOAD THIS PAGE!");
        $this->see("Results will be shown here when the process has finished");
        $this->see("It might take a while, especially if you have many domains or a slow connection.");
        $this->see("Please be patient while we're running the bulk protector");
    }

    public function seeNumberOfDomainsInfo()
    {
        $this->see("There are no domains on this server.");
        $this->seeElement(".//*[@id='statusContainer']/table/thead/tr");
    }
}
