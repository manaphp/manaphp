<?php

namespace ManaPHP;

use ManaPHP\Coroutine\Context\Stickyable;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\NotImplementedException;
use ManaPHP\Exception\UnauthorizedException;

/** @noinspection PhpMultipleClassesDeclarationsInOneFile */

class IdentityContext implements Stickyable
{
    /**
     * @var array
     */
    public $claims = [];
}

/**
 * Class ManaPHP\Identity
 *
 * @property-read \ManaPHP\IdentityContext $_context
 */
class Identity extends Component implements IdentityInterface
{
    /**
     * @return bool
     */
    public function isGuest()
    {
        return !$this->_context->claims;
    }

    /**
     * @param int $default
     *
     * @return int
     */
    public function getId($default = null)
    {
        $claims = $this->_context->claims;

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

    /**
     * @param string $default
     *
     * @return string
     */
    public function getName($default = null)
    {
        $claims = $this->_context->claims;

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

    /**
     * @param string $default
     *
     * @return string
     */
    public function getRole($default = 'guest')
    {
        $claims = $this->_context->claims;

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

        return strpos($role, ',') === false ? false : strpos(",$role,", ",$name,") !== false;
    }

    /**
     * @param string $role
     *
     * @return static
     */
    public function setRole($role)
    {
        $this->_context->claims['role'] = $role;

        return $this;
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return static
     */
    public function setClaim($name, $value)
    {
        $context = $this->_context;

        $context->claims[$name] = $value;

        return $this;
    }

    /**
     * @param array $claims
     *
     * @return static
     */
    public function setClaims($claims)
    {
        $this->_context->claims = $claims;

        return $this;
    }

    /**
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getClaim($name, $default = null)
    {
        $claims = $this->_context->claims;

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

    /**
     * @return array
     */
    public function getClaims()
    {
        return $this->_context->claims;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasClaim($name)
    {
        return isset($this->_context->claims[$name]);
    }

    public function authenticate($silent = true)
    {
        throw new NotImplementedException(__METHOD__);
    }
}