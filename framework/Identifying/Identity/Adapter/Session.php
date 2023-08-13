<?php
declare(strict_types=1);

namespace ManaPHP\Identifying\Identity\Adapter;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Http\SessionInterface;
use ManaPHP\Identifying\Identity;

class Session extends Identity
{
    #[Inject] protected SessionInterface $session;

    protected string $name = 'auth';

    public function authenticate(): array
    {
        return $this->session->get($this->name, []);
    }

    public function setClaims(array $claims): static
    {
        $this->session->set($this->name, $claims);
        return parent::setClaims($claims);
    }
}