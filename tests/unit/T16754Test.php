<?php

/* Commented until further clarification
set_include_path(get_include_path() . PATH_SEPARATOR .
    __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'library');

require_once 'Zend/Loader/Autoloader.php';

$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->setFallbackAutoloader(true);

class T16754Test extends PHPUnit_Framework_TestCase
{
    public function testGreekIdn()
    {
        require_once 'SpamFilter/Core.php';

        $this->assertTrue(SpamFilter_Core::validateDomain('κατασκευηιστοσελιδασ.gr'));
        $this->assertTrue(SpamFilter_Core::validateDomain('xn--mxaaaleanqbdcq4cxcebfdhq.gr'));
    }
}
*/