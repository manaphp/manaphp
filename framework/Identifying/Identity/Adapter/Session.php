<?php
declare(strict_types=1);

namespace ManaPHP\Identifying\Identity\Adapter;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\SessionInterface;
use ManaPHP\Identifying\Identity;

class Session extends Identity
{
    #[Autowired] protected SessionInterface $session;

    #[Autowired] protected string $name = 'auth';

    public function authenticate(): array
    {
        return $this->session->get($this->name, []);
    }

    public function set(array $claims): void
    {
        $this->session->set($this->name, $claims);
        parent::set($claims);
    }
}