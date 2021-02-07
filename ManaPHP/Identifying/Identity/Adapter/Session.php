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
    protected $name = 'auth';

    /**
     * @return array
     */
    public function authenticate()
    {
        return $this->session->get($this->name, []);
    }

    /**
     * @param array $claims
     *
     * @return static
     */
    public function setClaims($claims)
    {
        $this->session->set($this->name, $claims);
        return parent::setClaims($claims);
    }
}