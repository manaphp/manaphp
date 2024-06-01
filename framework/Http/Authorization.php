<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Context\ContextTrait;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Exception\ForbiddenException;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Helper\Str;
use ManaPHP\Http\Authorization\RoleRepositoryInterface;
use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Identifying\Identity\NoCredentialException;
use ManaPHP\Identifying\IdentityInterface;
use ReflectionClass;
use ReflectionMethod;
use function in_array;
use function preg_match;
use function str_contains;

class Authorization implements AuthorizationInterface
{
    use ContextTrait;

    #[Autowired] protected IdentityInterface $identity;
    #[Autowired] protected DispatcherInterface $dispatcher;
    #[Autowired] protected RouterInterface $router;
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected ResponseInterface $response;
    #[Autowired] protected ControllersInterface $controllers;
    #[Autowired] protected RoleRepositoryInterface $roleRepository;

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

            if ($actionAuthorize === null || $actionAuthorize->roles === []) {
                $permissions[] = $this->controllers->getPath($controller, $action);
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
                        /** @var Authorize $authorize */
                        $authorize = $attribute->newInstance();
                        if (in_array(Authorize::GUEST, $authorize->roles, true)) {
                            $permissions[] = $permission;
                        } elseif ($role !== Authorize::GUEST && in_array(Authorize::USER, $authorize->roles, true)) {
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
            if (($permissions = $this->roleRepository->getPermissions($role)) === null) {
                $permissions = ',' . implode(',', $this->buildAllowed($role)) . ',';
            }
            return $context->role_permissions[$role] = $permissions;
        } else {
            return $context->role_permissions[$role];
        }
    }

    protected function getAuthorize(string $controller, string $action): ?Authorize
    {
        $rMethod = new ReflectionMethod($controller, $action);

        if (($attribute = $rMethod->getAttributes(Authorize::class)[0] ?? null) === null) {
            $rClass = new ReflectionClass($controller);
            if (($attribute = $rClass->getAttributes(Authorize::class)[0] ?? null) === null) {
                return null;
            }
        }

        return $attribute->newInstance();
    }

    public function isAllowed(string $permission, ?array $roles = null): bool
    {
        $roles = $roles ?? $this->identity->getRoles();

        if (in_array(Authorize::ADMIN, $roles, true)) {
            return true;
        }

        if (str_contains($permission, '/')) {
            if (!str_starts_with($permission, '/')) {
                if (preg_match('#\\\\Areas\\\\(\w+)\\\\#', $this->dispatcher->getController(), $match) !== 1) {
                    throw new MisuseException(['permission is not start with /: {1}', $permission]);
                } else {
                    $permission = Str::snakelize($match[1]) . "/$permission";
                }
            }
        } else {
            if (($controller = $this->dispatcher->getController()) === null) {
                return false;
            }

            $action = Str::camelize($permission);
            if (($authorize = $this->getAuthorize($controller, $action)) !== null) {
                //allow guest
                if (in_array(Authorize::GUEST, $authorize->roles, true)) {
                    return true;
                }

                // role is guest
                if (in_array(Authorize::GUEST, $roles, true)) {
                    return false;
                }

                if ($roles !== [] && in_array(Authorize::USER, $authorize->roles, true)) {
                    return true;
                }

                foreach ($authorize->roles as $role) {
                    if (in_array($role, $roles, true)) {
                        return true;
                    }
                }

                return false;
            }

            $permission = $this->controllers->getPath($controller, $action);
        }

        $checked = ",$permission,";

        if ($roles === [] || $roles === [Authorize::GUEST]) {
            return str_contains($this->getAllowed(Authorize::GUEST), $checked);
        } elseif ($roles === [Authorize::USER]) {
            if (str_contains($this->getAllowed(Authorize::GUEST), $checked)) {
                return true;
            }
            return str_contains($this->getAllowed(Authorize::USER), $checked);
        } else {
            if (str_contains($this->getAllowed(Authorize::GUEST), $checked)) {
                return true;
            }

            if (str_contains($this->getAllowed(Authorize::USER), $checked)) {
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
                $redirect = $this->request->input('redirect', $this->request->url());
                $this->response->redirect(["/login?redirect=$redirect"]);
            }
        } else {
            throw new ForbiddenException('Access denied to resource');
        }
    }
}