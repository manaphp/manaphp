<?php
defined('UNIT_TESTS_ROOT') || require __DIR__ . '/bootstrap.php';

require __DIR__ . '/TApplication/Application.php';

class MvcApplicationTest extends TestCase
{

    public function test_useImplicitView()
    {
        $application = new \TApplication\Application();
        $this->assertEquals(true, $application->dump()['_implicitView']);

        $this->assertInstanceOf('ManaPHP\Mvc\Application', $application->useImplicitView(false));
        $this->assertEquals(false, $application->dump()['_implicitView']);
    }
}