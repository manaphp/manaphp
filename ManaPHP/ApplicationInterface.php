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
     * @return string
     */
    public function getAppPath();

    /**
     * @param int    $code
     * @param string $message
     *
     * @return void
     */
    public function abort($code, $message);
}