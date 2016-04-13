<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/12
 * Time: 17:44
 */

define('UNIT_TESTS_ROOT', str_replace('\\', '/', dirname(__DIR__)));

spl_autoload_register(function ($className) {
    static $frameworkRootPath;
    static $frameworkName;

    if (!isset($frameworkRootPath)) {
        $frameworkRootPath = dirname(__DIR__) . '/ManaPHP';
        $frameworkName = 'ManaPHP';
        $frameworkRootPath = dirname($frameworkRootPath);
    }

    if (strpos($className, $frameworkName) === 0) {
        $file = $frameworkRootPath . '/' . $className . '.php';
        $file = str_replace('\\', '/', $file);
        if (is_file($file)) {

            /** @noinspection PhpIncludeInspection */
            require $file;
            return true;
        }
    }

    if (strpos($className, 'Models') !== false) {
        $file = str_replace('\\', '/', __DIR__ . '/' . $className) . '.php';
        if (is_file($file)) {
            /** @noinspection PhpIncludeInspection */
            require $file;
            return true;
        }
    }

    return false;
});

class TestCase extends PHPUnit_Framework_TestCase
{

}