<?php
declare(strict_types=1);

namespace ManaPHP\Invoking\ValueResolver;

use ManaPHP\Cli\OptionsInterface;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Helper\Str;
use ManaPHP\Invoking\ScalarValueResolverInterface;
use ReflectionParameter;

class Options implements ScalarValueResolverInterface
{
    #[Inject] protected OptionsInterface $options;

    public function resolve(ReflectionParameter $parameter, ?string $type, string $name): mixed
    {
        $option = $name . '|' . $name[0];
        if (($value = $this->options->get($option)) === null) {
            $value = $this->options->get(Str::snakelize($name));
        }

        if ($value !== null) {
            return $type === 'bool' ? !in_array($value, ['0', 'false', 'FALSE'], true) : $value;
        } else {
            return null;
        }
    }
}