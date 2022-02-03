<?php
declare(strict_types=1);

namespace ManaPHP\Identifying\Identity\Adapter;

use ManaPHP\Identifying\Identity;

/**
 * @property-read \ManaPHP\Http\SessionInterface $session
 */
class Session extends Identity
{
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