<?php
declare(strict_types=1);

namespace ManaPHP\Invoking\ValueResolver;

use ManaPHP\Component;
use ManaPHP\Invoking\ScalarValueResolverInterface;

/**
 * @property-read \ManaPHP\Http\SessionInterface $session
 */
class Session extends Component implements ScalarValueResolverInterface
{
    public function resolve(?string $type, string $name): mixed
    {
        return $this->session->has($name) ? $this->session->get($name) : null;
    }
}