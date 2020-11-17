<?php

namespace ManaPHP\Identity\Adapter;

use ManaPHP\Identity;

/**
 * @property-read \ManaPHP\Http\SessionInterface $session
 */
class Session extends Identity
{
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

    public function setClaims($claims)
    {
        $this->session->set($this->_name, $claims);
        return parent::setClaims($claims);
    }
}