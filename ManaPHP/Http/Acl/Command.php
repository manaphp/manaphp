<?php

namespace ManaPHP\Http\Acl;

/**
 * @property-read \ManaPHP\Http\Acl\BuilderInterface $aclBuilder
 */
class Command extends \ManaPHP\Cli\Command
{
    /**
     * list acl of controllers
     *
     * @param string $role
     */
    public function listAction($role = '')
    {
        $authorization = $this->getShared('ManaPHP\Http\Authorization');
        foreach ($this->aclBuilder->getControllers() as $controller) {
            /** @var \ManaPHP\Http\Controller $controllerInstance */
            $controllerInstance = $this->getInstance($controller);
            $acl = $controllerInstance->getAcl();
            if ($role) {
                $actions = [];
                foreach ($this->aclBuilder->getActions($controller) as $action) {
                    if ($authorization->isAclAllowed($acl, $role, $action)) {
                        $actions[] = $action;
                    }
                }

                $this->console->writeLn($controller . ': ' . implode(',', $actions));
            } else {
                $this->console->writeLn($controller . ': ' . json_stringify($acl));
            }
        }
    }
}