<?php

require_once __DIR__ . '/cPanel.php';

/* Commented until further clarification
class T14645Test extends cPanel
{
    public function testDomainIsnotRemote()
    {
        $hookManagerMock = $this->getMock('SpamFilter_Hooks', array('GetRoute'));
        $hookManagerMock->expects($this->once())
            ->method('GetRoute')
            ->will($this->returnValue(array(
                'routes' => array(
                    'titania.echointernet.net:25',
                ),
            )));

        $model = new SpamFilter_PanelSupport_Cpanel(array());
        $this->assertFalse($model->IsRemoteDomain(array('domain' => 'testing.com', 'user' => 'null'), $hookManagerMock));
    }

    public function testDomainIsRemote()
    {
        $hookManagerMock = $this->getMock('SpamFilter_Hooks', array('GetRoute'));
        $hookManagerMock->expects($this->once())
            ->method('GetRoute')
            ->will($this->returnValue(array(
                'routes' => array(
                    'imx1.rambler.ru:25',
                    'imx2.rambler.ru:25',
                ),
            )));

        $model = new SpamFilter_PanelSupport_Cpanel(array());
        $this->assertTrue($model->IsRemoteDomain(array('domain' => 'rambler.ru', 'user' => 'null'), $hookManagerMock));
    }
}
*/