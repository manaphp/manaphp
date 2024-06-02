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
use function basename;
use function in_array;
use function preg_match;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function strpos;
use function substr;

class Authorization implements AuthorizationInterface
{
    use ContextTrait;

    #[Autowired] protected IdentityInterface $identity;
    #[Autowired] protected DispatcherInterface $dispatcher;
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected ResponseInterface $response;
    #[Autowired] protected ControllersInterface $controllers;
    #[Autowired] protected RoleRepositoryInterface $roleRepository;

    public function getPermission(string $controller, string $action): string
    {
        $controller = str_replace('\\', '.', $controller);
        $controller = basename($controller, 'Controller');
        $controller = str_replace('.Controllers.', '.', $controller);
        $controller = substr($controller, strpos($controller, '.') + 1);
        if (str_starts_with($controller, 'Areas.')) {
            $controller = substr($controller, 6);
        }
        return Str::hyphen($controller) . '::' . Str::hyphen(basename($action, 'Action'));
    }

    public function getAllowed(string $role): string
    {
        /** @var AuthorizationContext $context */
        $context = $this->getContext();

        if (!isset($context->role_permissions[$role])) {
            $permissions = $this->roleRepository->getPermissions($role) ?? '';
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

            $permission = $this->getPermission($controller, $action);
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