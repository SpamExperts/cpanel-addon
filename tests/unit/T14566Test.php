<?php

require_once __DIR__ . '/cPanel.php';

/* Commented until further clarification
class T14566Test extends cPanel
{
    public function testMxRecordAddedOncePerSecond()
    {
        $whmApiResponse1Mock = $this->getMock('Cpanel_Query_Object', array('getResponse'));
        $whmApiResponse1Mock->expects($this->any())
            ->method('getResponse')
            ->will($this->returnValue(array(
                'result' => array(
                    array('status' => 1),
                ),
            )));

        $cpanelApiMock = $this->getMock('Cpanel_PublicAPI', array('whm_api'));
        $cpanelApiMock->expects($this->any())
            ->method('whm_api')
            ->will($this->returnValue($whmApiResponse1Mock));

        $model = new SpamFilter_PanelSupport_Cpanel(array(
            'cpanel_api_instance' => $cpanelApiMock,
        ));

        $prevSecond = false;
        for ($i = 1; $i <= 5; $i++) {
            $second = time();
            $model->addMxRecord('xxx', 10, 'yyy');

            if (false === $prevSecond) {
                $prevSecond = $second;
            } else {
                $this->assertGreaterThanOrEqual(1, ($second - $prevSecond));
            }
        }
    }

    public function testMxRecordRemovedOncePerSecond()
    {
        $whmApiResponse1Mock = $this->getMock('Cpanel_Query_Object', array('getResponse'));
        $whmApiResponse1Mock->expects($this->any())
            ->method('getResponse')
            ->will($this->returnValue(array(
                'result' => array(
                    array('status' => 1),
                ),
            )));

        $cpanelApiMock = $this->getMock('Cpanel_PublicAPI', array('whm_api'));
        $cpanelApiMock->expects($this->any())
            ->method('whm_api')
            ->will($this->returnValue($whmApiResponse1Mock));

        $model = new SpamFilter_PanelSupport_Cpanel(array(
            'cpanel_api_instance' => $cpanelApiMock,
        ));

        $prevSecond = false;
        for ($i = 1; $i <= 5; $i++) {
            $second = time();
            $model->removeDNSRecord('xxx', 10);

            if (false === $prevSecond) {
                $prevSecond = $second;
            } else {
                $this->assertGreaterThanOrEqual(1, ($second - $prevSecond));
            }
        }
    }
}
*/