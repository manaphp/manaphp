<?php

namespace Tests;

use ManaPHP\Configuration\Configure;
use PHPUnit\Framework\TestCase;

class ConfigurationConfigureTest extends TestCase
{
    public function test_loadData()
    {
        //normal implicit equal
        $configure = new Configure();
        $this->assertEquals(['Home' => '/'], $configure->modules);
        $configure->loadData(['modules' => ['Api' => '/api']]);
        $this->assertEquals(['Api' => '/api'], $configure->modules);

        //implicit equal with env
        $configure = new Configure();
        $configure->loadData(['modules:dev' => ['Api' => '/api']], 'dev');
        $this->assertEquals(['Api' => '/api'], $configure->modules);

        $configure = new Configure();
        $configure->loadData(['modules:dev' => ['Api' => '/api']], 'test');
        $this->assertEquals(['Home' => '/'], $configure->modules);

        //normal explicit equal
        $configure = new Configure();
        $configure->loadData(['modules:dev=' => ['Api' => '/api']], 'dev');
        $this->assertEquals(['Api' => '/api'], $configure->modules);

        $configure = new Configure();
        $configure->loadData(['modules:dev=' => ['Api' => '/api']], 'test');
        $this->assertEquals(['Home' => '/'], $configure->modules);

        //add associate index type
        $configure = new Configure();
        $configure->loadData(['modules' => ['Home' => '/'], 'modules:dev+' => ['Api' => '/api']], 'dev');
        $this->assertEquals(['Home' => '/', 'Api' => '/api'], $configure->modules);

        $configure = new Configure();
        $configure->loadData(['modules' => ['Home' => '/'], 'modules:dev+' => ['Api' => '/api']], 'test');
        $this->assertEquals(['Home' => '/'], $configure->modules);

        //add number index type
        $configure = new Configure();
        $configure->loadData(['bootstraps' => ['logger'], 'bootstraps:dev+' => ['debugger']], 'dev');
        $this->assertEquals(['logger', 'debugger'], $configure->bootstraps);

        $configure = new Configure();
        $configure->loadData(['bootstraps' => ['logger'], 'bootstraps:dev+' => ['debugger']], 'test');
        $this->assertEquals(['logger'], $configure->bootstraps);

        //remove associate index type
        $configure = new Configure();
        $configure->loadData(['modules' => ['Home' => '/', 'Api' => '/api'], 'modules:dev-' => ['Api']], 'dev');
        $this->assertEquals(['Home' => '/'], $configure->modules);

        $configure = new Configure();
        $configure->loadData(['modules' => ['Home' => '/', 'Api' => '/api'], 'modules:dev-' => ['Api']], 'test');
        $this->assertEquals(['Home' => '/', 'Api' => '/api'], $configure->modules);

        //remove number index type
        $configure = new Configure();
        $configure->loadData(['bootstraps' => ['logger'], 'bootstraps:dev+' => ['debugger']], 'dev');
        $this->assertEquals(['logger', 'debugger'], $configure->bootstraps);

        $configure = new Configure();
        $configure->loadData(['bootstraps' => ['logger'], 'bootstraps:dev+' => ['debugger']], 'test');
        $this->assertEquals(['logger'], $configure->bootstraps);

        //!
        $configure = new Configure();
        $configure->loadData(['modules:!dev' => ['Api' => '/api']], 'dev');
        $this->assertEquals(['Home' => '/'], $configure->modules);

        $configure = new Configure();
        $configure->loadData(['modules:!dev' => ['Api' => '/api']], 'test');
        $this->assertEquals(['Api' => '/api'], $configure->modules);
    }
}