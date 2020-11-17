<?php

namespace ManaPHP\Http\Acl;

interface BuilderInterface
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