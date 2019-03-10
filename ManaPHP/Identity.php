<?php

namespace ManaPHP;

use ManaPHP\Exception\AuthenticationException;

class _IdentityContext
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
 * @property \ManaPHP\_IdentityContext $_context
 */
abstract class Identity extends Component implements IdentityInterface
{
    /**
     * Identity constructor.
     *
     * @param array $options
     */
    public function __construct($options = null)
    {
        $context = $this->_context = new _IdentityContext();

        if (is_array($options)) {
            if (isset($options['type'])) {
                $context->type = $options['type'];
            }
        }
    }

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
                throw new AuthenticationException('Not Authenticated');
            } else {
                return $default;
            }
        } elseif (!$context->type) {
            return $default;
        } else {
            $id = $context->type . '_id';
            return isset($context->claims[$id]) ? $context->claims[$id] : 0;
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
                throw new AuthenticationException('Not Authenticated');
            } else {
                return $default;
            }
        } elseif (!$context->type) {
            return $default;
        } else {
            $name = $context->type . '_name';
            return isset($context->claims[$name]) ? $context->claims[$name] : '';
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
            if (isset($context->claims['role'])) {
                return $context->claims['role'];
            } else {
                return isset($context->claims['admin_id']) && $context->claims['admin_id'] === 1 ? 'admin' : 'user';
            }
        } else {
            return $default;
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
     * @param string     $claim
     * @param string|int $value
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
     * @param string     $claim
     * @param string|int $default
     *
     * @return string|int
     */
    public function getClaim($claim, $default = null)
    {
        $context = $this->_context;

        return isset($context->claims[$claim]) ? $context->claims[$claim] : $default;
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
}