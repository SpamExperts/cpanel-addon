<?php

namespace Pages;

class BulkprotectPage
{
    const TITLE = "Bulkprotect";
    const DESCRIPTION_A = "On this page you can add all current domains to the spamfilter. Depending on the settings it may (or may not) execute certain actions.";
    const DESCRIPTION_B = "It is generally not required to run this more than once after the installation. Running bulk protect is usually only necessary after the first installation";

    const EXECUTE_BULKPROTECT_BTN = "//input[@id='submit']";
}
