<?php
declare(strict_types=1);

namespace ManaPHP\Invoking\ValueResolver;

use ManaPHP\Component;
use ManaPHP\Invoking\ScalarValueResolverInterface;

/**
 * @property-read \ManaPHP\Http\RequestInterface $request
 */
class Request extends Component implements ScalarValueResolverInterface
{
    public function resolve(?string $type, string $name): mixed
    {
        return $this->request->has($name) ? $this->request->get($name) : null;
    }
}