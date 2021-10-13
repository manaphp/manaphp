<?php

namespace ManaPHP\Cli\Commands;

/**
 * @property-read \ManaPHP\Http\Acl\BuilderInterface $aclBuilder
 */
class AclCommand extends \ManaPHP\Cli\Command
{
    /**
     * list acl of controllers
     *
     * @param string $role
     *
     * @return void
     */
    public function listAction($role = '')
    {
        $authorization = $this->injector->get('ManaPHP\Http\Authorization');
        foreach ($this->aclBuilder->getControllers() as $controller) {
            /** @var \ManaPHP\Http\Controller $controllerInstance */
            $controllerInstance = $this->injector->make($controller);
            $acl = $controllerInstance->getAcl();
            if ($role) {
                $actions = [];
                foreach ($this->aclBuilder->getActions($controller) as $action) {
                    if ($authorization->isAclAllowed($acl, $role, $action)) {
                        $actions[] = $action;
                    }
                }

                $this->console->writeLn($controller . ":\t " . implode(',', $actions));
            } else {
                $this->console->writeLn($controller . ":\t " . json_stringify($acl));
            }
        }
    }
}