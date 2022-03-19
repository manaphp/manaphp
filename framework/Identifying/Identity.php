<?php
declare(strict_types=1);

namespace ManaPHP\Identifying;

use ManaPHP\Component;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Exception\UnauthorizedException;

/**
 * @property-read \ManaPHP\Identifying\IdentityContext $context
 */
class Identity extends Component implements IdentityInterface
{
    protected function createContext(): IdentityContext
    {
        /** @var \ManaPHP\Identifying\IdentityContext $context */
        $context = parent::createContext();
        $context->claims = $this->authenticate();

        return $context;
    }

    public function isGuest(): bool
    {
        return !$this->context->claims;
    }

    public function getId(?int $default = null): ?int
    {
        $claims = $this->context->claims;

        if (!$claims) {
            if ($default === null) {
                throw new UnauthorizedException('Not Authenticated');
            }
            return $default;
        }

        if (isset($claims['id'])) {
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

    public function getName(string $default = null): string
    {
        $claims = $this->context->claims;

        if (!$claims) {
            if ($default === null) {
                throw new UnauthorizedException('Not Authenticated');
            }
            return $default;
        }

        if (isset($claims['name'])) {
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
        $claims = $this->context->claims;

        if (!$claims) {
            return $default;
        }

        if (isset($claims['role'])) {
            return $claims['role'];
        }

        if (isset($claims['admin_id'])) {
            return $claims['admin_id'] === 1 ? 'admin' : 'user';
        }

        throw new MisuseException('missing role in claims');
    }

    public function getRoles(): array
    {
        return preg_split('#[\s,]+#', $this->getRole(''), -1, PREG_SPLIT_NO_EMPTY);
    }

    public function isRole(string $name): bool
    {
        return in_array($name, $this->getRoles(), true);
    }

    public function setRole(string $role): static
    {
        $this->context->claims['role'] = $role;

        return $this;
    }

    public function setClaim(string $name, mixed $value): static
    {
        $context = $this->context;

        $context->claims[$name] = $value;

        return $this;
    }

    public function setClaims(array $claims): static
    {
        $this->context->claims = $claims;

        return $this;
    }

    public function getClaim(string $name, mixed $default = null): mixed
    {
        $claims = $this->context->claims;

        if (!$claims) {
            if ($default === null) {
                throw new UnauthorizedException('Not Authenticated');
            }
            return $default;
        }

        if (isset($claims[$name])) {
            return $claims[$name];
        }

        throw new MisuseException("missing $name in claims");
    }

    public function getClaims(): array
    {
        return $this->context->claims;
    }

    public function hasClaim(string $name): bool
    {
        return isset($this->context->claims[$name]);
    }

    public function authenticate(): array
    {
        return [];
    }

    public function encode(array $claims): string
    {
        throw new NotSupportedException(__METHOD__);
    }
}