<?php

require_once __DIR__ . '/cPanel.php';

/* Commented until further clarification
class T14648Test extends cPanel
{
    public function testMethodThrowsAnException()
    {
        $hookManagerMock = $this->getMock('SpamFilter_Hooks', array('GetRoute'));
        $hookManagerMock->expects($this->once())
            ->method('GetRoute')
            ->will($this->returnValue(array(
                'routes' => array(),
                'info' => array(
                    'additional' => "The domain 'testing.com' doesn't exist at this server",
                ),
                'reason' => 'API_UNHANDLED_ERROR',
            )));

        $model = new SpamFilter_PanelSupport_Cpanel(array());

        try {
            $model->IsRemoteDomain(array('domain' => 'testing.com', 'user' => 'null'), $hookManagerMock);
        } catch (RuntimeException $e) {
            return;
        }

        $this->fail('No exceptions');
    }
}
*/