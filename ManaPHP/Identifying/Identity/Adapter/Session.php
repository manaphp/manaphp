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
     * @return static
     */
    public function authenticate()
    {
        if ($claims = $this->session->get($this->_name, [])) {
            parent::setClaims($claims);
        }

        return $this;
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