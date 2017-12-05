<?php

namespace Tests;

use ManaPHP\Di\FactoryDefault;
use PHPUnit\Framework\TestCase;

class ConfigurationSettingsTest extends TestCase
{
    public function test_get()
    {
        $di = new FactoryDefault();
        $settings = $di->getShared('settings');
        $settings->delete('test', null);

        $this->assertEquals([], $settings->get('test'));
        $settings->set('test', ['a' => 1]);
        $this->assertEquals(1, $settings->get('test', 'a'));
    }

    public function test_set()
    {
        $di = new FactoryDefault();
        $settings = $di->getShared('settings');
        $settings->delete('test', null);

        $settings->set('test', 'a', 2);
        $this->assertEquals(2, $settings->get('test', 'a'));

        $settings->set('test', ['a' => 3]);
        $this->assertEquals(3, $settings->get('test', 'a'));
    }

    public function test_exists()
    {
        $di = new FactoryDefault();
        $settings = $di->getShared('settings');
        $settings->delete('test', null);
        $this->assertFalse($settings->exists('test'));
        $this->assertFalse($settings->exists('test', 'a'));
        $settings->set('test', 'a', 2);
        $this->assertTrue($settings->exists('test'));
        $this->assertTrue($settings->exists('test', 'a'));
    }

    public function test_delete()
    {
        $di = new FactoryDefault();
        $settings = $di->getShared('settings');
        $settings->delete('test', null);
        $this->assertFalse($settings->exists('test'));

        $settings->set('test', 'a', 2);
        $this->assertTrue($settings->exists('test'));

        $settings->delete('test', null);
        $this->assertFalse($settings->exists('test'));
    }
}