<?php
namespace ManaPHP\View\Flash;

/**
 * Interface ManaPHP\View\Flash\AdapterInterface
 *
 * @package flash
 */
interface AdapterInterface
{
    /**
     * @param string $type
     * @param string $message
     *
     * @return mixed
     */
    public function _message($type, $message);
}