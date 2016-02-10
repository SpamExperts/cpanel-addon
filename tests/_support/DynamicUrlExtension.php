<?php

class DynamicUrlExtension extends \Codeception\Extension
{
    // list events to listen to
    public static $events = array(
        'test.before' => 'beforeTest',
    );

    // methods that handle events
    public function beforeTest(\Codeception\Event\TestEvent $e)
    {
        $scenario = $e->getTest()->getScenario();
        $scenario->runStep(new \Codeception\Step\Action('setWebDriverUrl', [$scenario]));
        $scenario->runStep(new \Codeception\Step\Action('setupCpanelApi', [$scenario]));
        $scenario->runStep(new \Codeception\Step\Action('setupSpampanelApi', [$scenario]));
    }
}