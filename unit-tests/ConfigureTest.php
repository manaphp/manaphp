<?php
defined('UNIT_TESTS_ROOT') || require __DIR__ . '/bootstrap.php';

class ConfigureTest extends TestCase
{

    public function test_setAlias()
    {
        $configure = new \ManaPHP\Configure\Configure();

        $configure->setAlias('@app', '\data\www\app');
        $this->assertEquals('/data/www/app', $configure->getAlias('@app'));

        $configure->setAlias('@app', '\data\www\app2\\');
        $this->assertEquals('/data/www/app2', $configure->getAlias('@app'));

        $configure->setAlias('@data', '/app/data');
        $configure->setAlias('@log', '@data/log');
        $this->assertEquals('/app/data/log', $configure->getAlias('@log'));

        $configure->setAlias('@log', '@data');
        $this->assertEquals('/app/data', $configure->getAlias('@log'));

        try {
            $configure->setAlias('a', 'fdf');
            $this->assertFalse('why not?');
        } catch (\Exception $e) {
            $this->assertInstanceOf('\ManaPHP\Configure\Exception', $e);
        }
    }

    public function test_getAlias()
    {
        $configure = new \ManaPHP\Configure\Configure();
        $this->assertNull($configure->getAlias('@app'));

        $configure->setAlias('@app', '\app');
        $this->assertSame('/app', $configure->getAlias('@app'));

        try {
            $configure->getAlias('app');
            $this->assertFalse('why not?');
        } catch (\Exception $e) {
            $this->assertInstanceOf('\ManaPHP\Configure\Exception', $e);
        }
    }

    public function test_resolvePath()
    {
        $configure = new \ManaPHP\Configure\Configure();

        $this->assertEquals('/www/app', $configure->resolvePath('\www\app'));

        $configure->setAlias('@app', '/www/app');
        $this->assertEquals('/www/app/data', $configure->resolvePath('@app/data'));
    }
}