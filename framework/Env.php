<?php
declare(strict_types=1);

namespace ManaPHP;

use JsonSerializable;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Exception\FileNotFoundException;
use ManaPHP\Exception\InvalidArgumentException;
use ManaPHP\Exception\InvalidValueException;

class Env implements EnvInterface, JsonSerializable
{
    #[Autowired] protected AliasInterface $alias;

    #[Autowired] protected string $file = '@config/.env';

    public function load(): static
    {
        $file = $this->alias->resolve($this->file);

        if (!str_contains($this->file, '://') && !is_file($file)) {
            throw new FileNotFoundException(['.env file is not found: {file}', 'file' => $file]);
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES);

        $count = \count($lines);
        /** @noinspection ForeachInvariantsInspection */
        for ($i = 0; $i < $count; $i++) {
            $line = trim($lines[$i]);

            if ($line === '' || $line[0] === '#') {
                continue;
            }

            if ($pos = strpos($line, ' # ')) {
                $line = substr($line, 0, $pos);
            }
            if (str_starts_with($line, 'export ')) {
                $parts = explode('=', ltrim(substr($line, 7)), 2);
            } else {
                $parts = explode('=', $line, 2);
            }

            if (\count($parts) !== 2) {
                throw new InvalidValueException(['has no = character, invalid line: `{line}`', 'line' => $line]);
            }
            list($name, $value) = $parts;

            if ($value !== '') {
                $char = $value[0];
                if ($char === "'" || $char === '"') {
                    if (!str_ends_with($value, $char)) {
                        $value .= PHP_EOL;
                        for ($i++; $i < $count; $i++) {
                            $line = $lines[$i];
                            if (str_ends_with($line, $char)) {
                                $value .= $line;
                                break;
                            } else {
                                $value .= $line . PHP_EOL;
                            }
                        }
                    }
                    $value = substr($value, 1, -1);
                }

                if ($char !== "'" && str_contains($value, '$')) {
                    $value = preg_replace_callback('#\\$({\w+}|\w+)#', static function ($matches) use ($value) {
                        $ref_name = trim($matches[1], '{}');
                        if (($ref_value = getenv($ref_name)) === false) {
                            throw new InvalidValueException(['`{1}` ref variable is not exists: {2}', $ref_name, $value]
                            );
                        }
                        return $ref_value;
                    }, $value);
                }
            }

            if (getenv($name) === false) {
                putenv("$name=$value");
            }
        }

        return $this;
    }

    public function all(): array
    {
        return getenv() ?? [];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (($value = getenv($key)) === false) {
            if ($default === null) {
                throw new InvalidArgumentException(['`{key}` key value is not exists in .env file', 'key' => $key]);
            }
            return $default;
        }

        if (\is_array($default)) {
            if (\is_array($value)) {
                return $value;
            } elseif ($value !== '' && $value[0] === '{') {
                if (\is_array($r = json_parse($value))) {
                    return $r;
                } else {
                    throw new InvalidValueException(['the value of `{1}` key is not valid json format array', $key]);
                }
            } else {
                return preg_split('#[\s,]+#', $value, -1, PREG_SPLIT_NO_EMPTY);
            }
        } elseif (\is_int($default)) {
            return (int)$value;
        } elseif (\is_float($default)) {
            return (float)$value;
        } elseif (\is_bool($default)) {
            if (\is_bool($value)) {
                return $value;
            } elseif (\in_array(strtolower($value), ['1', 'on', 'true'], true)) {
                return true;
            } elseif (\in_array(strtolower($value), ['0', 'off', 'false'], true)) {
                return false;
            } else {
                throw new InvalidArgumentException(['`{1}` key value is not a valid bool value: {2}', $key, $value]);
            }
        } else {
            return $value;
        }
    }

    public function jsonSerialize(): array
    {
        return $this->all();
    }
}
