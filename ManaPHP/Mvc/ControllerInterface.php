<?php

namespace ManaPHP\Mvc;

/**
 * Interface ManaPHP\Mvc\ControllerInterface
 *
 * @package controller
 */
interface ControllerInterface
{
    /**
     * @return array
     */
    public function actionList();

    /**
     * @param string $name
     *
     * @return bool
     */
    public function actionExists($name);

    /**
     * @param string $action
     * @param array  $params
     *
     * @return mixed
     */
    public function actionExecute($action, $params = []);
}