<?php

namespace ManaPHP\Http\Acl;

use ManaPHP\Component;
use ManaPHP\Helper\LocalFS;

/**
 * @property-read \ManaPHP\AliasInterface $alias
 */
class Builder extends Component implements BuilderInterface
{
    /**
     * @var array
     */
    protected $controllers;

    /**
     * @return array
     */
    public function getControllers()
    {
        if ($this->controllers === null) {
            $controllers = [];

            foreach (LocalFS::glob('@app/Controllers/?*Controller.php') as $item) {
                $controller = str_replace($this->alias->resolve('@app'), 'App', $item);
                $controllers[] = str_replace('/', '\\', substr($controller, 0, -4));
            }

            foreach (LocalFS::glob('@app/Areas/*/Controllers/?*Controller.php') as $item) {
                $controller = str_replace($this->alias->resolve('@app'), 'App', $item);
                $controllers[] = str_replace('/', '\\', substr($controller, 0, -4));
            }

            $this->controllers = $controllers;
        }

        return $this->controllers;
    }

    /**
     * @param string $controller
     *
     * @return array
     */
    public function getActions($controller)
    {
        $actions = [];
        foreach (get_class_methods($controller) as $method) {
            if ($method[0] === '_' || !preg_match('#^(.*)Action$#', $method, $match)) {
                continue;
            }

            $actions[] = $match[1];
        }

        return $actions;
    }
}