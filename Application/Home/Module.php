<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/11/21
 * Time: 22:21
 */
namespace Application\Home;

use ManaPHP\Db\Adapter\Mysql;
use ManaPHP\DbInterface;
use ManaPHP\Loader;
use ManaPHP\Mvc\ModuleInterface;

class Module implements ModuleInterface
{
    public function registerAutoloaders($di)
    {
    }

    public function registerServices($di)
    {

    }
}