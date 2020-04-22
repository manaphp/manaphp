<?php

namespace ManaPHP\Identity\Adapter;

use ManaPHP\Identity;
use ManaPHP\Identity\NoCredentialException;

/**
 * Class Session
 *
 * @package ManaPHP\Identity\Adapter
 * @property-read \ManaPHP\Http\SessionInterface $session
 */
class Session extends Identity
{
    protected $_name = 'auth';

    /**
     * @param bool $silent
     *
     * @return static
     */
    public function authenticate($silent = true)
    {
        if ($claims = $this->session->get($this->_name, [])) {
            parent::setClaims($claims);
            return $this;
        } elseif ($silent) {
            return $this;
        } else {
            throw new NoCredentialException('no token');
        }
    }

    public function setClaims($claims)
    {
        $this->session->set($this->_name, $claims);
        return parent::setClaims($claims);
    }
}