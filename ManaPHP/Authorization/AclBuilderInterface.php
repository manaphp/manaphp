<?php

namespace ManaPHP\Authorization;

interface AclBuilderInterface
{
    /**
     * @return array
     */
    public function getControllers();

    /**
     * @param string $controller
     *
     * @return array
     */
    public function getActions($controller);
}