<?php
declare(strict_types=1);

namespace ManaPHP\Invoking\ValueResolver;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Invoking\ScalarValueResolverInterface;

class Request implements ScalarValueResolverInterface
{
    #[Inject] protected RequestInterface $request;

    public function resolve(?string $type, string $name): mixed
    {
        return $this->request->has($name) ? $this->request->get($name) : null;
    }
}