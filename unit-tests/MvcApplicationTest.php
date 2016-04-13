<?php
defined('UNIT_TESTS_ROOT') || require __DIR__ . '/bootstrap.php';

require __DIR__ . '/TApplication/Application.php';

class MvcApplicationTest extends TestCase
{
    public function test_construct()
    {
        $application = new \TApplication\Application();

        $this->assertEquals(str_replace('\\', '/', __DIR__) . '/TApplication', $application->getAppPath());
        $this->assertEquals('TApplication', $application->getAppNamespace());
    }

    public function test_useImplicitView()
    {
        $application = new \TApplication\Application();
        $properties = $application->__debugInfo();
        $this->assertEquals(true, $properties['_implicitView']);

        $this->assertInstanceOf('ManaPHP\Mvc\Application', $application->useImplicitView(false));
        $properties = $application->__debugInfo();
        $this->assertEquals(false, $properties['_implicitView']);
    }
}