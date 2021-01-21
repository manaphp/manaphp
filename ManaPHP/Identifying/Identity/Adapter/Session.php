<?php

namespace ManaPHP\Identifying\Identity\Adapter;

use ManaPHP\Identifying\Identity;

/**
 * @property-read \ManaPHP\Http\SessionInterface $session
 */
class Session extends Identity
{
    /**
     * @var string
     */
    protected $_name = 'auth';

    /**
     * @return array
     */
    public function authenticate()
    {
        return $this->session->get($this->_name, []);
    }

    /**
     * @param array $claims
     *
     * @return static
     */
    public function setClaims($claims)
    {
        $this->session->set($this->_name, $claims);
        return parent::setClaims($claims);
    }
}