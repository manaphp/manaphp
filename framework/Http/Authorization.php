<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Context\ContextTrait;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Exception\ForbiddenException;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Helper\Str;
use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Identifying\Identity\NoCredentialException;
use ManaPHP\Identifying\IdentityInterface;
use ManaPHP\Model\ModelInterface;
use ReflectionClass;
use ReflectionMethod;

class Authorization implements AuthorizationInterface
{
    use ContextTrait;

    #[Autowired] protected IdentityInterface $identity;
    #[Autowired] protected DispatcherInterface $dispatcher;
    #[Autowired] protected RouterInterface $router;
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected ResponseInterface $response;
    #[Autowired] protected ControllersInterface $controllers;

    public function getPermissions(string $controller): array
    {
        $permissions = [];

        $rClass = new ReflectionClass($controller);
        if (($attribute = $rClass->getAttributes(Authorize::class)[0] ?? null) !== null) {
            $controllerAuthorize = $attribute->newInstance();
        } else {
            /** @var Authorize $controllerAuthorize */
            $controllerAuthorize = null;
        }

        foreach ($this->controllers->getActions($controller) as $action) {
            $rMethod = new ReflectionMethod($controller, $action . 'Action');
            if (($attribute = $rMethod->getAttributes(Authorize::class)[0] ?? null) !== null) {
                $actionAuthorize = $attribute->newInstance();
            } else {
                /** @var Authorize $actionAuthorize */
                $actionAuthorize = $controllerAuthorize;
            }

            if ($actionAuthorize === null || $actionAuthorize->role === null) {
                $permissions[] = $this->controllers->getPath($controller, $action);
            }
        }

        if ($controllerAuthorize?->role !== null && str_starts_with($controllerAuthorize->role, '@')) {
            $refer = substr($controllerAuthorize->role, 1);
            $method = Str::camelize($refer) . 'Action';
            if (method_exists($controller, $method)) {
                $rMethod = new ReflectionMethod($controller, $method);
                if (!isset($rMethod->getAttributes(Authorize::class)[0])) {
                    $permissions[] = $this->controllers->getPath($controller, $refer);
                }
            } else {
                $permissions[] = $this->controllers->getPath($controller, $refer);
            }
        }

        return $permissions;
    }

    public function buildAllowed(string $role, array $granted = []): array
    {
        $permissions = [];
        foreach ($this->controllers->getControllers() as $controller) {
            foreach ($this->controllers->getActions($controller) as $action) {
                $permission = $this->controllers->getPath($controller, $action);

                if (in_array($permission, $granted, true)) {
                    $permissions[] = $permission;
                } else {
                    $rMethod = new ReflectionMethod($controller, $action . 'Action');
                    if (($attribute = $rMethod->getAttributes(Authorize::class)[0] ?? null) === null) {
                        $rClass = new ReflectionClass($controller);
                        $attribute = $rClass->getAttributes(Authorize::class)[0] ?? null;
                    }

                    if ($attribute !== null) {
                        $authorize = $attribute->newInstance();
                        if ($authorize->role === null) {
                            null;
                        } elseif (str_starts_with($authorize->role, '@')) {
                            $refer = substr($authorize->role, 1);
                            $refer_permission = $this->controllers->getPath($controller, $refer);
                            if (in_array($refer_permission, $granted, true)) {
                                $permissions[] = $permission;
                            } else {
                                $method = Str::camelize($refer) . 'Action';
                                if (method_exists($controller, $method)) {
                                    $rMethod = new ReflectionMethod($controller, $method);
                                    if (($attribute = $rMethod->getAttributes(Authorize::class)[0] ?? null) !== null) {
                                        $authorize = $attribute->newInstance();
                                        if ($authorize->role === $role) {
                                            $permissions[] = $permission;
                                        }
                                    }
                                }
                            }
                        } elseif ($role === $authorize->role) {
                            $permissions[] = $permission;
                        }
                    }
                }
            }
        }

        sort($permissions);

        return $permissions;
    }

    public function getAllowed(string $role): string
    {
        /** @var AuthorizationContext $context */
        $context = $this->getContext();

        if (!isset($context->role_permissions[$role])) {
            /** @var ModelInterface $roleModel */
            $roleModel = null;
            if (class_exists('App\Areas\Rbac\Models\Role')) {
                $roleModel = 'App\Areas\Rbac\Models\Role';
            } elseif (class_exists('App\Models\Role')) {
                $roleModel = 'App\Models\Role';
            }

            if ($roleModel) {
                $permissions = $roleModel::value(['role_name' => $role], 'permissions');
            } else {
                $permissions = ',' . implode(',', $this->buildAllowed($role)) . ',';
            }

            return $context->role_permissions[$role] = $permissions;
        } else {
            return $context->role_permissions[$role];
        }
    }

    protected function getAuthorize(string $controller, string $action): ?Authorize
    {
        $rMethod = new ReflectionMethod($controller, $action . 'Action');

        if (($attribute = $rMethod->getAttributes(Authorize::class)[0] ?? null) === null) {
            $rClass = new ReflectionClass($controller);
            if (($attribute = $rClass->getAttributes(Authorize::class)[0] ?? null) === null) {
                return null;
            }
        }

        /** @var Authorize $authorize */
        $authorize = $attribute->newInstance();

        if ($authorize->role !== null && str_starts_with($authorize->role, '@')) {
            $refer = substr($authorize->role, 1);
            $referMethod = Str::camelize($refer) . 'Action';

            if (!method_exists($controller, $referMethod)) {
                return null;
            }

            $rMethod = new ReflectionMethod($controller, $referMethod);
            if (($attribute = $rMethod->getAttributes(Authorize::class)[0] ?? null) === null) {
                return null;
            }

            /** @var Authorize $authorize */
            $authorize = $attribute->newInstance();

            if ($authorize->role !== null && str_starts_with($authorize->role, '@')) {
                throw new MisuseException(['Too many indirect refer: {1}Action', $action]);
            }
        }

        return $authorize;
    }

    public function isAllowed(string $permission, ?array $roles = null): bool
    {
        $roles = $roles ?? $this->identity->getRoles();

        if (in_array('admin', $roles, true)) {
            return true;
        }

        if (str_contains($permission, '/')) {
            if (!str_starts_with($permission, '/')) {
                if (($area = $this->dispatcher->getArea()) === null) {
                    throw new MisuseException(['permission is not start with /: {1}', $permission]);
                } else {
                    $permission = Str::snakelize($area) . "/$permission";
                }
            }
        } else {
            $controllerInstance = $this->dispatcher->getControllerInstance();
            if ($controllerInstance === null) {
                return false;
            }

            $controller = $controllerInstance::class;
            $action = Str::camelize($permission);
            if (($authorize = $this->getAuthorize($controller, $action)) !== null) {
                if (($allowed = $authorize->isAllowed($roles)) !== null) {
                    return $allowed;
                }
            }

            $permission = $this->controllers->getPath($controller, $action);
        }

        $checked = ",$permission,";

        if ($roles === [] || $roles === ['guest']) {
            return str_contains($this->getAllowed('guest'), $checked);
        } elseif ($roles === ['user']) {
            if (str_contains($this->getAllowed('guest'), $checked)) {
                return true;
            }
            return str_contains($this->getAllowed('user'), $checked);
        } else {
            if (str_contains($this->getAllowed('guest'), $checked)) {
                return true;
            }

            if (str_contains($this->getAllowed('user'), $checked)) {
                return true;
            }

            foreach ($roles as $role) {
                if (str_contains($this->getAllowed($role), $checked)) {
                    return true;
                }
            }

            return false;
        }
    }

    public function authorize(): void
    {
        if ($this->isAllowed($this->dispatcher->getAction())) {
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