<?php

namespace ManaPHP\Identity\Adapter;

use ManaPHP\Identity;
use ManaPHP\Identity\NoCredentialException;

/**
 * Class Jwt
 *
 * @package ManaPHP\Identity\Adapter
 * @property-read \ManaPHP\Http\RequestInterface $request
 */
class Jwt extends Identity
{
    /**
     * @var string
     */
    protected $_scope;

    /**
     * Jwt constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        $this->_scope = $options['scope'] ?? $this->configure->id;
    }

    /**
     * @return static
     */
    /**
     * @param bool $silent
     *
     * @return static
     */
    public function authenticate($silent = true)
    {
        if ($token = $this->request->getToken()) {
            $claims = $this->jwt->scopedDecode($token, $this->_scope);
            return $this->setClaims($claims);
        } elseif ($silent) {
            return $this;
        } else {
            throw new NoCredentialException('no token');
        }
    }
}