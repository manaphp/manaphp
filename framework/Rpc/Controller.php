<?php
declare(strict_types=1);

namespace ManaPHP\Rpc;

use ManaPHP\Component;
use ManaPHP\Logging\Logger\LogCategorizable;

/**
 * @property-read \ManaPHP\Rpc\RequestInterface                      $request
 * @property-read \ManaPHP\Rpc\Controller\ArgumentsResolverInterface $argumentsResolver
 */
class Controller extends Component implements LogCategorizable
{
    public function categorizeLog(): string
    {
        return basename(str_replace('\\', '.', static::class), 'Controller');
    }

    public function invoke(string $action): mixed
    {
        $method = $action . 'Action';
        $arguments = $this->argumentsResolver->resolve($this, $method);

        return $this->$method(...$arguments);
    }
}