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

    public function testMailHandlerRemovesDomainAndUpdatesMxRecordsSafelyWhenSwitchedToNonLocal()
    {
        $loggerMock = $this->getMockBuilder('\SpamFilter_Logger')
            ->setMethods(['info', 'debug'])
            ->getMock();

        $configMock = new \stdClass;
        $configMock->handle_route_switching = true;
        $configMock->provision_dns = true;

        $sut = $this->getMockBuilder('\SpamFilter_Hooks')
            ->setConstructorArgs([ $loggerMock, $configMock ])
            ->setMethods([ 'SpamFilter_Hooks', 'AddDomain', 'DelDomain', 'safeResetDns' ])
            ->getMock();

        $sut->expects($this->never())
            ->method('AddDomain');
        $sut->expects($this->exactly(2))
            ->method('DelDomain')
            ->with($this->equalTo(self::DOMAIN), $this->equalTo(true), $this->equalTo(false))
            ->will($this->returnValue(true));
        $sut->expects($this->exactly(2))
            ->method('safeResetDns')
            ->with($this->equalTo(self::DOMAIN))
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
            ->setMethods([ 'SpamFilter_Hooks', 'AddDomain', 'DelDomain', 'safeResetDns' ])
            ->getMock();

        $sut->expects($this->never())
            ->method('safeResetDns');
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

    public function testSafeDnsResetDoesNotRemoveNonspamfilterMxRecords()
    {
        $loggerMock = $this->getMockBuilder('\SpamFilter_Logger')
            ->setMethods(['info', 'debug'])
            ->getMock();

        $configMock = new \stdClass;

        $panelMock = $this->getMockBuilder('\SpamFilter_PanelSupport_Cpanel')
            ->setMethods(['getMxRecords', 'addMxRecord', 'removeDNSRecord'])
            ->getMock();

        $panelMock->expects($this->never())
            ->method('addMxRecord');
        $panelMock->expects($this->never())
            ->method('removeDNSRecord');
        $panelMock->expects($this->once())
            ->method('getMxRecords')
            ->with($this->equalTo(self::DOMAIN))
            ->will($this->returnValue([
                [ 'exchange' => 'mx1.host.test', 'Line' => 10 ],
                [ 'exchange' => 'mx2.host.test', 'Line' => 20 ],
            ]));

        $sut = $this->getMockBuilder('\SpamFilter_Hooks')
            ->setConstructorArgs([ $loggerMock, $configMock, $panelMock ])
            ->setMethods([ 'SpamFilter_Hooks', 'getFilteringClusterHostnames' ])
            ->getMock();

        $sut->expects($this->once())
            ->method('getFilteringClusterHostnames')
            ->will($this->returnValue([ '10' => 'mx1.spamfilter.test', '20' => 'mx2.spamfilter.test' ]));

        $sut->safeResetDns(self::DOMAIN);
    }

    public function testSafeDnsResetRemovesSpamfilterMxRecords()
    {
        $loggerMock = $this->getMockBuilder('\SpamFilter_Logger')
            ->setMethods(['info', 'debug'])
            ->getMock();

        $configMock = new \stdClass;

        $panelMock = $this->getMockBuilder('\SpamFilter_PanelSupport_Cpanel')
            ->setMethods(['getMxRecords', 'addMxRecord', 'removeDNSRecord'])
            ->getMock();

        $panelMock->expects($this->never())
            ->method('addMxRecord');
        $panelMock->expects($this->once())
            ->method('removeDNSRecord')
            ->with(self::DOMAIN, $this->equalTo(20));
        $panelMock->expects($this->once())
            ->method('getMxRecords')
            ->with($this->equalTo(self::DOMAIN))
            ->will($this->returnValue([
                [ 'exchange' => 'mx1.host.test', 'Line' => 10 ],
                [ 'exchange' => 'mx1.spamfilter.test', 'Line' => 20 ],
            ]));

        $sut = $this->getMockBuilder('\SpamFilter_Hooks')
            ->setConstructorArgs([ $loggerMock, $configMock, $panelMock ])
            ->setMethods([ 'SpamFilter_Hooks', 'getFilteringClusterHostnames' ])
            ->getMock();

        $sut->expects($this->once())
            ->method('getFilteringClusterHostnames')
            ->will($this->returnValue([ '10' => 'mx1.spamfilter.test', '20' => 'mx2.spamfilter.test' ]));

        $sut->safeResetDns(self::DOMAIN);
    }

    public function testSafeDnsResetAddsBackupMxRecordWhenAllExistingMxRecordsWereRemoved()
    {
        $loggerMock = $this->getMockBuilder('\SpamFilter_Logger')
            ->setMethods(['info', 'debug'])
            ->getMock();

        $configMock = new \stdClass;

        $panelMock = $this->getMockBuilder('\SpamFilter_PanelSupport_Cpanel')
            ->setMethods(['getMxRecords', 'addMxRecord', 'removeDNSRecord'])
            ->getMock();

        $panelMock->expects($this->once())
            ->method('addMxRecord')
            ->with($this->equalTo(self::DOMAIN), $this->equalTo(10), $this->equalTo('cpanel.host.test'));
        $panelMock->expects($this->exactly(2))
            ->method('removeDNSRecord')
            ->withConsecutive(
                [ $this->equalTo(self::DOMAIN), $this->equalTo(20) ],
                [ $this->equalTo(self::DOMAIN), $this->equalTo(10) ]
            );
        $panelMock->expects($this->once())
            ->method('getMxRecords')
            ->with($this->equalTo(self::DOMAIN))
            ->will($this->returnValue([
                [ 'exchange' => 'mx1.spamfilter.test', 'Line' => 10 ],
                [ 'exchange' => 'mx2.spamfilter.test', 'Line' => 20 ],
            ]));

        $sut = $this->getMockBuilder('\SpamFilter_Hooks')
            ->setConstructorArgs([ $loggerMock, $configMock, $panelMock ])
            ->setMethods([ 'SpamFilter_Hooks', 'getFilteringClusterHostnames', 'getFallbackMxRecordHostname' ])
            ->getMock();

        $sut->expects($this->once())
            ->method('getFilteringClusterHostnames')
            ->will($this->returnValue([ '10' => 'mx1.spamfilter.test', '20' => 'mx2.spamfilter.test' ]));
        $sut->expects($this->once())
            ->method('getFallbackMxRecordHostname')
            ->will($this->returnValue('cpanel.host.test'));

        $sut->safeResetDns(self::DOMAIN);
    }

    public function testSafeDnsResetAddsBackupMxRecordWhenThereWereNoMxRecordsBefore()
    {
        $loggerMock = $this->getMockBuilder('\SpamFilter_Logger')
            ->setMethods(['info', 'debug'])
            ->getMock();

        $configMock = new \stdClass;

        $panelMock = $this->getMockBuilder('\SpamFilter_PanelSupport_Cpanel')
            ->setMethods(['getMxRecords', 'addMxRecord', 'removeDNSRecord'])
            ->getMock();

        $panelMock->expects($this->once())
            ->method('addMxRecord')
            ->with($this->equalTo(self::DOMAIN), $this->equalTo(10), $this->equalTo('cpanel.host.test'));
        $panelMock->expects($this->never())
            ->method('removeDNSRecord');
        $panelMock->expects($this->once())
            ->method('getMxRecords')
            ->with($this->equalTo(self::DOMAIN))
            ->will($this->returnValue([]));

        $sut = $this->getMockBuilder('\SpamFilter_Hooks')
            ->setConstructorArgs([ $loggerMock, $configMock, $panelMock ])
            ->setMethods([ 'SpamFilter_Hooks', 'getFilteringClusterHostnames', 'getFallbackMxRecordHostname' ])
            ->getMock();

        $sut->expects($this->once())
            ->method('getFilteringClusterHostnames')
            ->will($this->returnValue([ '10' => 'mx1.spamfilter.test', '20' => 'mx2.spamfilter.test' ]));
        $sut->expects($this->once())
            ->method('getFallbackMxRecordHostname')
            ->will($this->returnValue('cpanel.host.test'));

        $sut->safeResetDns(self::DOMAIN);
    }
}
