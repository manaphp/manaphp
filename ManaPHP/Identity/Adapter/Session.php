<?php
namespace ManaPHP\Identity\Adapter;

use ManaPHP\Identity;
use ManaPHP\Identity\NoCredentialException;

/**
 * Class Session
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
        $claims = $this->session->get($this->_name, []);
        if (!$claims && !$silent) {
            throw new NoCredentialException('');
        }
        return $this->setClaims($claims);
    }
}