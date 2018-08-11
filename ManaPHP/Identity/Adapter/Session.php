<?php
namespace ManaPHP\Identity\Adapter;

use ManaPHP\Identity;

/**
 * Class Session
 * @package ManaPHP\Identity\Adapter
 * @property \ManaPHP\Http\SessionInterface $session
 */
class Session extends Identity
{
    protected $_name = 'auth';

    /**
     * @return bool
     */
    public function authenticate()
    {
        $claims = $this->session->get($this->_name, []);
        $this->setClaims($claims);

        return (bool)$claims;
    }
}