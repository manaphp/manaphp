<?php
namespace ManaPHP;

use ManaPHP\Exception\ForbiddenException;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Identity\NoCredentialException;
use ManaPHP\Utility\Text;

/**
 * Class Authorization
 * @package ManaPHP
 *
 * @property \ManaPHP\DispatcherInterface $dispatcher
 * @property \ManaPHP\RouterInterface     $router
 */
class Authorization extends Component implements AuthorizationInterface
{
    /**
     * @var array
     */
    protected $_acl;

    /**
     * @param array  $acl
     * @param string $role
     * @param string $action
     *
     * @return bool
     */
    public function isAclAllow($acl, $role, $action)
    {
        if (isset($acl[$action])) {
            $roles = $acl[$action];
            if ($roles[0] === '@') {
                $original_action = substr($roles, 1);
                if (isset($acl[$original_action])) {
                    $roles = $acl[$original_action];
                    if ($roles[0] === '@') {
                        throw new MisuseException(['`:action` original action is not allow indirect.', 'action' => $original_action]);
                    }
                } else {
                    $roles = null;
                }
            }
        } else {
            $roles = null;
        }

        if ($roles === null && isset($acl['*'])) {
            $roles = $acl['*'];
        }

        if ($roles === null || $roles === '') {
            return $role === 'admin';
        } elseif ($roles === '*' || $roles === 'guest') {
            return true;
        } elseif ($roles === 'user') {
            return $role !== 'guest';
        } elseif ($roles === 'admin') {
            return $role === 'admin';
        } elseif ($roles === $role) {
            return true;
        } elseif ($role === 'guest') {
            return false;
        } elseif ($role === 'admin') {
            return true;
        } else {
            return preg_match("#\b$role\b#", $roles) === 1;
        }
    }

    /**
     * @param string $permission
     *
     * @return array
     */
    public function inferControllerAction($permission)
    {
        $area = null;
        if ($permission[0] === '/') {
            if ($areas = $this->router->getAreas()) {
                $pos = strpos($permission, '/', 1);
                $area = Text::camelize($pos === false ? substr($permission, 1) : substr($permission, 1, $pos - 1));
                if (in_array($area, $areas, true)) {
                    $permission = $pos === false || $pos === strlen($permission) - 1 ? '' : (string)substr($permission, $pos + 1);
                } else {
                    $area = null;
                    $permission = substr($permission, 1);
                }
            } else {
                $permission = substr($permission, 1);
            }
        } else {
            $area = $this->dispatcher->getArea();
        }

        if ($permission === '') {
            $controller = 'Index';
            $action = 'index';
        } elseif ($pos = strpos($permission, '/')) {
            if ($pos === false || $pos === strlen($permission) - 1) {
                $controller = Text::camelize($pos === false ? $permission : substr($permission, 0, -1));
                $action = 'index';
            } else {
                $controller = Text::camelize(substr($permission, 0, $pos));
                $action = lcfirst(Text::camelize(substr($permission, $pos + 1)));
            }
        } else {
            $controller = Text::camelize($permission);
            $action = 'index';
        }

        if ($area) {
            return ["@ns.app\\Areas\\$area\\Controllers\\{$controller}Controller", $action];
        } else {
            return ["@ns.app\\Controllers\\{$controller}Controller", $action];
        }
    }

    /**
     * @param string $controllerClassName
     * @param string $action
     *
     * @return string
     */
    public function generatePath($controllerClassName, $action)
    {
        $controllerClassName = str_replace('\\', '/', $controllerClassName);
        $action = Text::underscore($action);

        if (preg_match('#Areas/([^/]+)/Controllers/(.*)Controller$#', $controllerClassName, $match)) {
            $area = Text::underscore($match[1]);
            $controller = Text::underscore($match[2]);

            if ($action === 'index') {
                if ($controller === 'index') {
                    return $area === 'index' ? '/' : "/$area";
                } else {
                    return "/$area/$controller";
                }
            } else {
                return "/$area/$controller/$action";
            }
        } elseif (preg_match('#/Controllers/(.*)Controller#', $controllerClassName, $match)) {
            $controller = Text::underscore($match[1]);

            if ($action === 'index') {
                return $controller === 'index' ? '/' : "/$controller";
            } else {
                return "/$controller/$action";
            }
        } else {
            throw new MisuseException(['invalid controller `:controller`', 'controller' => $controllerClassName]);
        }
    }

    /**
     * @param string $role
     *
     * @return array
     */
    public function getAllowed($role)
    {
        $paths = [];

        $controllers = [];
        foreach (glob($this->alias->resolve('@app/Areas/*/Controllers/*Controller.php')) as $item) {
            $controller = str_replace($this->alias->resolve('@app'), $this->alias->resolveNS('@ns.app'), $item);
            $controllers[] = str_replace('/', '\\', substr($controller, 0, -4));
        }

        foreach (glob($this->alias->resolve('@app/Controllers/*Controller.php')) as $item) {
            $controllers[] = $this->alias->resolveNS('@ns.app\\Controllers\\' . basename($item, '.php'));
        }

        foreach ($controllers as $controller) {
            /** @var  \ManaPHP\Controller $controllerInstance */
            $controllerInstance = new $controller();
            $acl = $controllerInstance->getAcl();

            foreach (get_class_methods($controller) as $method) {
                if (preg_match('#^(.*)Action$#', $method, $match)) {
                    $action = $match[1];
                    if ($this->isAclAllow($acl, $role, $action)) {
                        $paths[] = $this->generatePath($controller, $action);
                    }
                }
            }
        }

        return $paths;
    }

    /**
     * Check whether a user is allowed to access a permission
     *
     * @param string $permission
     * @param string $role
     *
     * @return bool
     */
    public function isAllowed($permission = null, $role = null)
    {
        if ($permission && strpos($permission, '/') !== false) {
            list($controllerClassName, $action) = $this->inferControllerAction($permission);
            $controllerClassName = $this->alias->resolveNS($controllerClassName);
            if (!isset($this->_acl[$controllerClassName])) {
                /** @var \ManaPHP\Controller $controllerInstance */
                $controllerInstance = new $controllerClassName;
                $this->_acl[$controllerClassName] = $controllerInstance->getAcl();
            }
        } else {
            $controllerInstance = $this->dispatcher->getControllerInstance();
            $controllerClassName = get_class($controllerInstance);
            $action = $permission ? lcfirst(Text::camelize($permission)) : $this->dispatcher->getAction();

            if (!isset($this->_acl[$controllerClassName])) {
                $this->_acl[$controllerClassName] = $controllerInstance->getAcl();
            }
        }

        $acl = $this->_acl[$controllerClassName];

        $role = $role ?: $this->identity->getRole();
        if (strpos($role, ',') === false) {
            return $this->isAclAllow($acl, $role, $action);
        } else {
            foreach (explode($role, ',') as $r) {
                if ($this->isAclAllow($acl, $r, $action)) {
                    return true;
                }
            }

            return false;
        }
    }

    /**
     * @param string $permission
     * @param string $role
     *
     * @throws \ManaPHP\Identity\NoCredentialException
     * @throws \ManaPHP\Exception\ForbiddenException
     */
    public function authorize($permission = null, $role = null)
    {
        $role = $role ?: $this->identity->getRole();

        if (!$this->isAllowed($permission, $role)) {
            if ($role === 'guest') {
                throw new NoCredentialException('No Credential or Invalid Credential');
            } else {
                throw new ForbiddenException('Access denied to resource');
            }
        }
    }
}