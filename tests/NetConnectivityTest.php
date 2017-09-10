<?php

namespace Tests;

use ManaPHP\Net\Connectivity;
use PHPUnit\Framework\TestCase;

class NetConnectivityTest extends TestCase
{
    public function test_test()
    {
        $connectivity = new Connectivity();

        $this->assertTrue($connectivity->test('127.0.0.1:6379'));
        $this->assertTrue($connectivity->test('redis://127.0.0.1:6379'));
        $this->assertTrue($connectivity->test('redis://127.0.0.1'));
        $this->assertTrue($connectivity->test('redis://127.0.0.1:6379/1/test'));
        $this->assertTrue($connectivity->test('127.0.0.1:6379/1/test'));
        $this->assertFalse($connectivity->test('127.0.0.1:1'));

        $this->assertTrue($connectivity->test('127.0.0.1:1,127.0.0.1:6379'));
        $this->assertTrue($connectivity->test('127.0.0.1:6379,127.0.0.1:1'));

        $this->assertTrue($connectivity->test('redis://127.0.0.1,127.0.0.1:1'));
        $this->assertTrue($connectivity->test('mongodb://127.0.0.1:6379,127.0.0.2:6379/?replicaSet=myReplSetName&readPreference=primaryPreferred'));
    }

    public function test_wait()
    {
        $connectivity = new Connectivity();

        $this->assertTrue($connectivity->wait(['redis://127.0.0.1']));
        $this->assertFalse($connectivity->wait(['redis://127.0.0.1:1']));
        $this->assertTrue($connectivity->wait(['redis://127.0.0.1:1,redis://127.0.0.1']));
    }
}