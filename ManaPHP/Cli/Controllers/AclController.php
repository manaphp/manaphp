<?php

namespace ManaPHP\Cli\Controllers;

use ManaPHP\Cli\Controller;

/**
 * Class AclController
 *
 * @package ManaPHP\Cli\Controllers
 *
 * @property-read \ManaPHP\Http\Authorization\AclBuilderInterface $aclBuilder
 */
class AclController extends Controller
{
    /**
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