<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/12
 * Time: 17:44
 */

require dirname(__DIR__) . '/ManaPHP/Loader.php';
require dirname(__DIR__) . '/ManaPHP/helpers.php';

define('MANAPHP_COROUTINE_ENABLED', false);
$loader = new \ManaPHP\Loader();
$loader->registerNamespaces(['Tests' => __DIR__]);