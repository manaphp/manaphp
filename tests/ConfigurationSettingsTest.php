<?php

namespace Tests;

use ManaPHP\Di\FactoryDefault;
use PHPUnit\Framework\TestCase;

class ConfigurationSettingsTest extends TestCase
{
    public function test_get()
    {
        $di = new FactoryDefault();
        $settings = $di->get('settings');

        $settings->delete('test');
        $this->assertEquals([], $settings->get('test'));
        $settings->set('test', ['a' => 1]);
        $this->assertEquals(['a' => 1], $settings->get('test'));
    }

    public function test_set()
    {
        $di = new FactoryDefault();
        $settings = $di->get('settings');
        $settings->delete('test');

        $settings->set('test', ['a' => 2]);
        $this->assertEquals(['a' => 2], $settings->get('test'));

        $settings->set('test', ['a' => 3]);
        $this->assertEquals(['a' => 3], $settings->get('test'));
    }

    public function test_exists()
    {
        $di = new FactoryDefault();
        $settings = $di->get('settings');
        $settings->delete('test');
        $this->assertFalse($settings->exists('test'));

        $settings->set('test', ['a' => 2]);
        $this->assertTrue($settings->exists('test'));
    }

    public function test_delete()
    {
        $di = new FactoryDefault();
        $settings = $di->get('settings');
        $settings->delete('test');
        $this->assertFalse($settings->exists('test'));

        $settings->set('test', ['a' => 2]);
        $this->assertTrue($settings->exists('test'));

        $settings->delete('test');
        $this->assertFalse($settings->exists('test'));
    }
}