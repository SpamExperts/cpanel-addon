<?php

/** @noinspection PhpUndefinedClassInspection */
class SpamFilter_HooksTest extends \PHPUnit_Framework_TestCase
{
    const DOMAIN = 'example.test';
    const MAIL_HANDLER_LOCAL = 'local';
    const MAIL_HANDLER_REMOTE = 'remote';
    const MAIL_HANDLER_AUTO = 'auto';

    protected function setUp()
    {
        require_once LIB_ROOT . '/SpamFilter/Hooks.php';
        require_once LIB_ROOT . '/SpamFilter/ResellerAPI.php';
    }

    protected function tearDown()
    {
    }

    // tests
    public function testMailHandlerSwitchIgnoredWhenDisabledInSettings()
    {
        $loggerMock = $this->getMockBuilder('\SpamFilter_Logger')
            ->setMethods(['info', 'debug'])
            ->getMock();

        $configMock = new \stdClass;
        $configMock->handle_route_switching = false;

        $sut = $this->getMockBuilder('\SpamFilter_Hooks')
            ->setConstructorArgs([ $loggerMock, $configMock ])
            ->setMethods([ 'SpamFilter_Hooks', 'AddDomain', 'DelDomain' ])
            ->getMock();

        $sut->expects($this->never())
            ->method('AddDomain');
        $sut->expects($this->never())
            ->method('DelDomain');

        foreach ([ self::MAIL_HANDLER_LOCAL, self::MAIL_HANDLER_REMOTE, self::MAIL_HANDLER_AUTO ] as $type) {
            $this->assertFalse($sut->setMailHandling(self::DOMAIN, $type));
        }
    }

    public function testMailHandlerAddsDomainWhenSwitchedToLocal()
    {
        $loggerMock = $this->getMockBuilder('\SpamFilter_Logger')
            ->setMethods(['info', 'debug'])
            ->getMock();

        $configMock = new \stdClass;
        $configMock->handle_route_switching = true;

        $sut = $this->getMockBuilder('\SpamFilter_Hooks')
            ->setConstructorArgs([ $loggerMock, $configMock ])
            ->setMethods([ 'SpamFilter_Hooks', 'AddDomain', 'DelDomain' ])
            ->getMock();

        $sut->expects($this->once())
            ->method('AddDomain')
            ->with($this->equalTo(self::DOMAIN))
            ->will($this->returnValue(true));
        $sut->expects($this->never())
            ->method('DelDomain');

        $this->assertTrue($sut->setMailHandling(self::DOMAIN, self::MAIL_HANDLER_LOCAL));
    }

    public function testMailHandlerRemovesDomainAndUpdatesMxRecordsWhenSwitchedToNonLocal()
    {
        $loggerMock = $this->getMockBuilder('\SpamFilter_Logger')
            ->setMethods(['info', 'debug'])
            ->getMock();

        $configMock = new \stdClass;
        $configMock->handle_route_switching = true;
        $configMock->provision_dns = true;

        $sut = $this->getMockBuilder('\SpamFilter_Hooks')
            ->setConstructorArgs([ $loggerMock, $configMock ])
            ->setMethods([ 'SpamFilter_Hooks', 'AddDomain', 'DelDomain' ])
            ->getMock();

        $sut->expects($this->never())
            ->method('AddDomain');
        $sut->expects($this->exactly(2))
            ->method('DelDomain')
            ->with($this->equalTo(self::DOMAIN), $this->equalTo(true), $this->equalTo(true))
            ->will($this->returnValue(true));

        foreach ([ self::MAIL_HANDLER_REMOTE, self::MAIL_HANDLER_AUTO ] as $type) {
            $this->assertTrue($sut->setMailHandling(self::DOMAIN, $type));
        }
    }

    public function testMailHandlerRemovesDomainAndSkipsMxRecordsUpdateIfDnsProvisioningDisabledWhenSwitchedToNonLocal()
    {
        $loggerMock = $this->getMockBuilder('\SpamFilter_Logger')
            ->setMethods(['info', 'debug'])
            ->getMock();

        $configMock = new \stdClass;
        $configMock->handle_route_switching = true;
        $configMock->provision_dns = false;

        $sut = $this->getMockBuilder('\SpamFilter_Hooks')
            ->setConstructorArgs([ $loggerMock, $configMock ])
            ->setMethods([ 'SpamFilter_Hooks', 'AddDomain', 'DelDomain' ])
            ->getMock();

        $sut->expects($this->never())
            ->method('AddDomain');
        $sut->expects($this->exactly(2))
            ->method('DelDomain')
            ->with($this->equalTo(self::DOMAIN), $this->equalTo(true), $this->equalTo(false))
            ->will($this->returnValue(true));

        foreach ([ self::MAIL_HANDLER_REMOTE, self::MAIL_HANDLER_AUTO ] as $type) {
            $this->assertTrue($sut->setMailHandling(self::DOMAIN, $type));
        }
    }
}
