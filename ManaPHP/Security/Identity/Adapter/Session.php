<?php
namespace ManaPHP\Security\Identity\Adapter;

use ManaPHP\Security\Identity;

/**
 * Class Session
 * @package ManaPHP\Security\Identity\Adapter
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
        $this->_claims = $claims;

        return (bool)$claims;
    }
}