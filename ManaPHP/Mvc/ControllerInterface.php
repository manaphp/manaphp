<?php

namespace ManaPHP\Mvc;

/**
 * ManaPHP\Mvc\ControllerInterface initializer
 */
interface ControllerInterface
{
    /**
     * @param string $action
     *
     * @return string|false
     */
    public function getCachedResponse($action);

    /**
     * @param string $action
     * @param string $content
     *
     * @return void
     */
    public function setCachedResponse($action, $content);
}