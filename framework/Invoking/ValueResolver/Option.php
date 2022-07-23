<?php
declare(strict_types=1);

namespace ManaPHP\Invoking\ValueResolver;

use ManaPHP\Component;
use ManaPHP\Invoking\ScalarValueResolverInterface;

/**
 * @property-read \ManaPHP\Cli\RequestInterface $request
 */
class Option extends Component implements ScalarValueResolverInterface
{
    public function resolve(?string $type, string $name): mixed
    {
        if ($this->request->has($name)) {
            return $this->request->get($name, $type === 'array' ? [] : '');
        } else {
            return null;
        }
    }
}