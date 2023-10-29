<?php
declare(strict_types=1);

namespace ManaPHP\Cli;

use JsonSerializable;
use Stringable;

class Options implements OptionsInterface, JsonSerializable, Stringable
{
    protected array $argv;
    protected array $options = [];

    public function parse(array $argv): array
    {
        $this->argv = $argv;
        $this->options = [];

        while ($argv !== []) {
            if (!str_starts_with($argv[0], '-')) {
                $this->options[''] = implode(' ', $argv);
                break;
            }

            $option = array_shift($argv);

            if (str_contains($option, '=')) {
                $parts = explode('=', $option, 2);
                $this->options[ltrim($parts[0], '-')] = $parts[1];
                continue;
            }

            if ($option === '--') {
                $this->options[''] = implode(' ', $argv);
                break;
            } elseif (str_starts_with($option, '--') || \strlen($option) === 2) {
                if ($argv === []) {
                    $value = '';
                } elseif ($argv[0] !== '-' && str_starts_with($argv[0], '-')) {
                    $value = '';
                } else {
                    $value = array_shift($argv);
                }
                $this->options[ltrim($option, '-')] = $value;
            } else {
                foreach (str_split(substr($option, 1)) as $c) {
                    $this->options[$c] = '';
                }
            }
        }

        return $this->options;
    }

    public function all(): array
    {
        return $this->options;
    }

    public function get(string $name, mixed $default = null): ?string
    {
        foreach (explode('|', $name) as $option) {
            if (isset($this->options[$option])) {
                return $this->options[$option];
            } elseif (str_contains($option, '_')) {
                $option = strtr($option, '_', '-');
                if (isset($this->options[$option])) {
                    return $this->options[$option];
                }
            }
        }

        return $default;
    }

    public function has(string $name): bool
    {
        return $this->get($name) !== null;
    }

    public function jsonSerialize(): array
    {
        return $this->options;
    }

    public function __toString(): string
    {
        return json_stringify($this->jsonSerialize());
    }
}