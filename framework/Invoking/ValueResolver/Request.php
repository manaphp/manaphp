<?php
declare(strict_types=1);

namespace ManaPHP\Invoking\ValueResolver;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Invoking\ScalarValueResolverInterface;
use ReflectionParameter;

class Request implements ScalarValueResolverInterface
{
    #[Autowired] protected RequestInterface $request;

    public function resolve(ReflectionParameter $parameter, ?string $type, string $name): mixed
    {
        $value = $this->request->input($name);

        if ($value === '') {
            return $type === 'string' ? '' : null;
        } else {
            return $value;
        }
    }
}