<?php
declare(strict_types=1);

namespace ManaPHP\Invoking\ValueResolver;

use ManaPHP\Component;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Invoking\ScalarValueResolverInterface;

class Request extends Component implements ScalarValueResolverInterface
{
    #[Inject] protected RequestInterface $request;

    public function resolve(?string $type, string $name): mixed
    {
        return $this->request->has($name) ? $this->request->get($name) : null;
    }
}