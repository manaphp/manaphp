<?php
declare(strict_types=1);

namespace ManaPHP\Invoking\ValueResolver;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Http\SessionInterface;
use ManaPHP\Invoking\ScalarValueResolverInterface;

class Session implements ScalarValueResolverInterface
{
    #[Inject] protected SessionInterface $session;

    public function resolve(?string $type, string $name): mixed
    {
        return $this->session->has($name) ? $this->session->get($name) : null;
    }
}