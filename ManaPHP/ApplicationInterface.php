<?php
namespace ManaPHP;

/**
 * Interface ManaPHP\ApplicationInterface
 *
 * @package application
 */
interface ApplicationInterface
{
    /**
     * @return void
     */
    public function registerServices();

    /**
     * @return void
     */
    public function main();

    /**
     * @return array
     */
    public function getModules();

    /**
     * @param int    $code
     * @param string $message
     *
     * @return void
     */
    public function abort($code, $message);
}