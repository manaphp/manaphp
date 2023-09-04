<?php
declare(strict_types=1);

namespace ManaPHP\Invoking\ValueResolver;

use ManaPHP\Cli\OptionsInterface;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Invoking\ScalarValueResolverInterface;
use ReflectionParameter;

class Options implements ScalarValueResolverInterface
{
    #[Inject] protected OptionsInterface $options;

    public function resolve(ReflectionParameter $parameter, ?string $type, string $name): mixed
    {
        if ($this->options->has($name)) {
            return $this->options->get($name, $type === 'array' ? [] : '');
        } else {
            return null;
        }
    }
}