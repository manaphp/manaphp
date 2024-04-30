<?php
declare(strict_types=1);

namespace ManaPHP\Invoking\ValueResolver;

use ManaPHP\Cli\OptionsInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Helper\Str;
use ManaPHP\Invoking\ScalarValueResolverInterface;
use ReflectionParameter;
use function in_array;

class Options implements ScalarValueResolverInterface
{
    #[Autowired] protected OptionsInterface $options;

    public function resolve(ReflectionParameter $parameter, ?string $type, string $name): string|bool|null
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