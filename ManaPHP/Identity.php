<?php

namespace ManaPHP;

use ManaPHP\Coroutine\Context\Stickyable;
use ManaPHP\Exception\NotImplementedException;
use ManaPHP\Exception\UnauthorizedException;

class IdentityContext implements Stickyable
{
    /**
     * @var string
     */
    public $type;

    /**
     * @var array
     */
    public $claims = [];
}

/**
 * Class ManaPHP\Identity
 * @property \ManaPHP\IdentityContext $_context
 */
class Identity extends Component implements IdentityInterface
{
    /**
     * @return bool
     */
    public function isGuest()
    {
        $context = $this->_context;

        return !$context->claims;
    }

    /**
     * @param int $default
     *
     * @return int
     */
    public function getId($default = null)
    {
        $context = $this->_context;

        if (!$context->claims) {
            if ($default === null) {
                throw new UnauthorizedException('Not Authenticated');
            } else {
                return $default;
            }
        } elseif (!$context->type) {
            return $default;
        } else {
            $id = $context->type . '_id';
            return $context->claims[$id] ?? 0;
        }
    }

    /**
     * @param string $default
     *
     * @return string
     */
    public function getName($default = null)
    {
        $context = $this->_context;

        if (!$context->claims) {
            if ($default === null) {
                throw new UnauthorizedException('Not Authenticated');
            } else {
                return $default;
            }
        } elseif (!$context->type) {
            return $default;
        } else {
            $name = $context->type . '_name';
            return $context->claims[$name] ?? '';
        }
    }

    /**
     * @param string $default
     *
     * @return string
     */
    public function getRole($default = 'guest')
    {
        $context = $this->_context;

        if ($context->claims) {
            return $context->claims['role'] ?? (isset($context->claims['admin_id']) && $context->claims['admin_id'] === 1 ? 'admin' : 'user');
        } else {
            return $default;
        }
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function isRole($name)
    {
        $role = $this->getRole();

        if ($name === $role) {
            return true;
        }

        if (strpos($role, ',') === false) {
            return false;
        } else {
            return strpos(",$role,", ",$name,") !== false;
        }
    }

    /**
     * @param string $role
     *
     * @return static
     */
    public function setRole($role)
    {
        $context = $this->_context;

        $context->claims['role'] = $role;

        return $this;
    }

    /**
     * @param string $claim
     * @param mixed  $value
     *
     * @return static
     */
    public function setClaim($claim, $value)
    {
        $context = $this->_context;

        $context->claims[$claim] = $value;

        return $this;
    }

    /**
     * @param array $claims
     *
     * @return static
     */
    public function setClaims($claims)
    {
        $context = $this->_context;

        if ($claims && (!$context->type || !isset($claims[$context->type]))) {
            foreach ($claims as $claim => $value) {
                if (strlen($claim) > 3 && strrpos($claim, '_id', -3) !== false) {
                    $context->type = substr($claim, 0, -3);
                    break;
                }
            }
        }
        $context->claims = $claims;

        return $this;
    }

    /**
     * @param string $claim
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getClaim($claim, $default = null)
    {
        $context = $this->_context;

        return $context->claims[$claim] ?? $default;
    }

    /**
     * @return array
     */
    public function getClaims()
    {
        return $this->_context->claims;
    }

    /**
     * @param string $claim
     *
     * @return bool
     */
    public function hasClaim($claim)
    {
        $context = $this->_context;

        return isset($context->claims[$claim]);
    }

    public function authenticate($silent = true)
    {
        throw new NotImplementedException(__METHOD__);
    }
}