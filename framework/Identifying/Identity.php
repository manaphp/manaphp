<?php
declare(strict_types=1);

namespace ManaPHP\Identifying;

use ManaPHP\Context\ContextCreatorInterface;
use ManaPHP\Context\ContextTrait;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\UnauthorizedException;
use ManaPHP\Http\Controller\Attribute\Authorize;

class Identity implements IdentityInterface, ContextCreatorInterface
{
    use ContextTrait;

    #[Autowired] protected array $keys = [];

    public function createContext(): IdentityContext
    {
        /** @var IdentityContext $context */
        $context = $this->contextManager->makeContext($this);
        $context->claims = $this->authenticate();

        return $context;
    }

    public function isGuest(): bool
    {
        /** @var IdentityContext $context */
        $context = $this->getContext();

        return !$context->claims;
    }

    public function getId(): int
    {
        /** @var IdentityContext $context */
        $context = $this->getContext();

        $claims = $context->claims;

        if (!$claims) {
            throw new UnauthorizedException('Not Authenticated');
        }

        if (($key = $this->keys['id'] ?? null) !== null) {
            return $claims[$key];
        } elseif (isset($claims['id'])) {
            return $claims['id'];
        }

        foreach ($claims as $name => $value) {
            if (($pos = strrpos($name, '_id', -3)) !== false) {
                $name_name = substr($name, 0, $pos) . '_name';

                if (isset($claims[$name_name])) {
                    return $value;
                }
            }
        }

        throw new MisuseException('missing id in claims');
    }

    public function getName(): string
    {
        /** @var IdentityContext $context */
        $context = $this->getContext();

        $claims = $context->claims;

        if (!$claims) {
            throw new UnauthorizedException('Not Authenticated');
        }

        if (($key = $this->keys['name'] ?? null) !== null) {
            return $claims[$key];
        } elseif (isset($claims['name'])) {
            return $claims['name'];
        }

        foreach ($claims as $name => $value) {
            if (($pos = strrpos($name, '_id', -3)) !== false) {
                $name_name = substr($name, 0, $pos) . '_name';

                if (isset($claims[$name_name])) {
                    return $claims[$name_name];
                }
            }
        }

        throw new MisuseException('missing name in claims');
    }

    public function getRole(string $default = 'guest'): string
    {
        /** @var IdentityContext $context */
        $context = $this->getContext();

        $claims = $context->claims;

        if (!$claims) {
            return $default;
        }

        if (($key = $this->keys['role'] ?? null) !== null) {
            return $claims[$key];
        } elseif (isset($claims['role'])) {
            return $claims['role'];
        }

        if (isset($claims['admin_id'])) {
            return $claims['admin_id'] === 1 ? Authorize::ADMIN : Authorize::USER;
        }

        throw new MisuseException('missing role in claims');
    }

    public function getRoles(): array
    {
        return preg_split('#[\s,]+#', $this->getRole(''), -1, PREG_SPLIT_NO_EMPTY);
    }

    public function set(array $claims): void
    {
        /** @var IdentityContext $context */
        $context = $this->getContext();

        $context->claims = $claims;
    }

    public function authenticate(): array
    {
        return [];
    }
}