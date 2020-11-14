<?php

namespace ManaPHP\Http;

use ManaPHP\Component;
use ManaPHP\Exception\ForbiddenException;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Helper\Str;
use ManaPHP\Identity\NoCredentialException;

/** @noinspection PhpMultipleClassesDeclarationsInOneFile */

class AuthorizationContext
{
    /**
     * @var array
     */
    public $role_permissions;
}

/**
 * Class Authorization
 *
 * @package ManaPHP
 *
 * @property-read \ManaPHP\Http\DispatcherInterface               $dispatcher
 * @property-read \ManaPHP\RouterInterface                        $router
 * @property-read \ManaPHP\Http\RequestInterface                  $request
 * @property-read \ManaPHP\Http\ResponseInterface                 $response
 * @property-read \ManaPHP\Http\Authorization\AclBuilderInterface $aclBuilder
 * @property-read \ManaPHP\Http\AuthorizationContext              $_context
 */
class Authorization extends Component implements AuthorizationInterface
{
    /**
     * @param array  $acl
     * @param string $role
     * @param string $action
     *
     * @return bool
     */
    public function isAclAllowed($acl, $role, $action)
    {
        if (($roles = $acl[$action] ?? null) && $roles[0] === '@') {
            $original_action = substr($roles, 1);
            /** @noinspection NotOptimalIfConditionsInspection */
            if (($roles = $acl[$original_action] ?? null) && $roles[0] === '@') {
                throw new MisuseException(['`%s` original action is not allow indirect.', $original_action]);
            }
        }

        if ($roles === null && isset($acl['*'])) {
            $roles = $acl['*'];
        }

        if ($role === 'admin' || $roles === '*' || $roles === 'guest') {
            return true;
        } elseif ($role === 'guest') {
            return false;
        } else {
            return $roles === 'user' || $roles === $role || preg_match("#\b$role\b#", $roles) === 1;
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
                $area = Str::camelize($pos === false ? substr($permission, 1) : substr($permission, 1, $pos - 1));
                if (in_array($area, $areas, true)) {
                    if ($pos === false || $pos === strlen($permission) - 1) {
                        $permission = '';
                    } else {
                        $permission = substr($permission, $pos + 1);
                    }
                } else {
                    $area = null;
                    $permission = $permission === '/' ? '' : (string)substr($permission, 1);
                }
            } else {
                $permission = $permission === '/' ? '' : (string)substr($permission, 1);
            }
        } else {
            $area = $this->dispatcher->getArea();
        }

        if ($permission === '') {
            $controller = 'Index';
            $action = 'index';
        } elseif ($pos = strpos($permission, '/')) {
            if ($pos === false || $pos === strlen($permission) - 1) {
                $controller = Str::camelize($pos === false ? $permission : substr($permission, 0, -1));
                $action = 'index';
            } else {
                $controller = Str::camelize(substr($permission, 0, $pos));
                $action = lcfirst(Str::camelize(substr($permission, $pos + 1)));
            }
        } else {
            $controller = Str::camelize($permission);
            $action = 'index';
        }

        if ($area) {
            return ["App\\Areas\\$area\\Controllers\\{$controller}Controller", $action];
        } else {
            return ["App\\Controllers\\{$controller}Controller", $action];
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
        $action = Str::underscore($action);

        if (preg_match('#Areas/([^/]+)/Controllers/(.*)Controller$#', $controllerClassName, $match)) {
            $area = Str::underscore($match[1]);
            $controller = Str::underscore($match[2]);

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
            $controller = Str::underscore($match[1]);

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
     * @param array  $explicit_permissions
     *
     * @return array
     */
    public function buildAllowed($role, $explicit_permissions = [])
    {
        $paths = [];

        $controllers = $this->aclBuilder->getControllers();

        foreach ($controllers as $controller) {
            /** @var \ManaPHP\Http\Controller $controllerInstance */
            $controllerInstance = $this->getInstance($controller);
            $acl = $controllerInstance->getAcl();

            foreach ($this->aclBuilder->getActions($controller) as $action) {
                $path = $this->generatePath($controller, $action);
                if ($role === 'guest') {
                    if ($this->isAclAllowed($acl, 'guest', $action)) {
                        $paths[] = $path;
                    }
                } elseif ($role === 'user') {
                    /** @noinspection NotOptimalIfConditionsInspection */
                    if ($this->isAclAllowed($acl, 'user', $action) && !$this->isAclAllowed($acl, 'guest', $action)) {
                        $paths[] = $path;
                    }
                } elseif ($this->isAclAllowed($acl, 'user', $action)) {
                    null;
                } else {
                    if ($this->isAclAllowed($acl, $role, $action)) {
                        $paths[] = $path;
                    } elseif (in_array($path, $explicit_permissions, true)) {
                        $paths[] = $path;
                    } elseif (isset($acl[$action]) && $acl[$action][0] === '@') {
                        $real_path = $this->generatePath($controller, substr($acl[$action], 1));
                        if (in_array($real_path, $explicit_permissions, true)) {
                            $paths[] = $path;
                        }
                    } elseif (isset($acl['*']) && $acl['*'][0] === '@') {
                        $real_path = $this->generatePath($controller, substr($acl['*'], 1));
                        if (in_array($real_path, $explicit_permissions, true)) {
                            $paths[] = $path;
                        }
                    }
                }
            }
        }

        sort($paths);

        return $paths;
    }

    /**
     * @param string $role
     *
     * @return string
     */
    public function getAllowed($role)
    {
        static $builtin;

        if (isset($builtin[$role])) {
            return $builtin[$role];
        }

        $context = $this->_context;

        if (!isset($context->role_permissions[$role])) {
            /** @var \ManaPHP\ModelInterface $roleModel */
            $roleModel = null;
            if (class_exists('App\Areas\Rbac\Models\Role')) {
                $roleModel = 'App\Areas\Rbac\Models\Role';
            } elseif (class_exists('App\Models\Role')) {
                $roleModel = 'App\Models\Role';
            }

            if ($roleModel) {
                $permissions = $roleModel::value(['role_name' => $role], 'permissions');
                if ($role === 'guest') {
                    null;
                } elseif ($role === 'user') {
                    $guest_permissions = $roleModel::valueOrDefault(['role_name' => 'guest'], 'permissions', '');
                    $permissions = $guest_permissions . $permissions;
                } else {
                    $guest_permissions = $roleModel::valueOrDefault(['role_name' => 'guest'], 'permissions', '');
                    $user_permissions = $roleModel::valueOrDefault(['role_name' => 'user'], 'permissions', '');
                    $permissions = $guest_permissions . $user_permissions . $permissions;
                }
                return $context->role_permissions[$role] = $permissions;
            } else {
                return $builtin[$role] = ',' . implode(',', $this->buildAllowed($role)) . ',';
            }
        } else {
            return $context->role_permissions[$role];
        }
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
        $role = $role ?: $this->identity->getRole();
        if ($role === 'admin') {
            return true;
        }

        if ($role !== 'guest' && $permission && $permission[0] === '/') {
            if (str_contains($role, ',')) {
                foreach (explode(',', $role) as $r) {
                    if (str_contains($this->getAllowed($r), ",$permission,")) {
                        return true;
                    }
                }
            } else {
                if (str_contains($this->getAllowed($role), ",$permission,")) {
                    return true;
                }
            }
        }

        if ($permission && str_contains($permission, '/')) {
            list($controllerClassName, $action) = $this->inferControllerAction($permission);
        } else {
            /** @var \ManaPHP\Http\Controller $controllerInstance */
            $controllerInstance = $this->dispatcher->getControllerInstance();
            $controllerClassName = get_class($controllerInstance);
            $action = $permission ? lcfirst(Str::camelize($permission)) : $this->dispatcher->getAction();
            $acl = $controllerInstance->getAcl();

            if ($this->isAclAllowed($acl, $role, $action)) {
                return true;
            }

            if ($role === 'guest') {
                return false;
            }

            if (isset($acl[$action]) && $acl[$action][0] === '@') {
                $action = substr($acl[$action], 1);
            }
        }

        $permission = $this->generatePath($controllerClassName, $action);
        if (str_contains($role, ',')) {
            foreach (explode(',', $role) as $r) {
                if (str_contains($this->getAllowed($r), ",$permission,")) {
                    return true;
                }
            }
        } else {
            if (str_contains($this->getAllowed($role), ",$permission,")) {
                return true;
            }
        }

        return false;
    }

    /**
     *
     * @throws \ManaPHP\Identity\NoCredentialException
     * @throws \ManaPHP\Exception\ForbiddenException
     */
    public function authorize()
    {
        if ($this->isAllowed()) {
            return;
        }

        if ($this->identity->isGuest()) {
            if ($this->request->isAjax()) {
                throw new NoCredentialException('No Credential or Invalid Credential');
            } else {
                $redirect = input('redirect', $this->request->getUrl());
                $this->response->redirect(["/login?redirect=$redirect"]);
            }
        } else {
            throw new ForbiddenException('Access denied to resource');
        }
    }
}