<?php

declare(strict_types=1);

namespace ManaPHP;

use JsonSerializable;
use ManaPHP\Alias\Path;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Exception\FileNotFoundException;
use ManaPHP\Exception\InvalidArgumentException;
use ManaPHP\Exception\InvalidValueException;
use function count;
use function in_array;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;

class Env implements EnvInterface, JsonSerializable
{
    #[Autowired] protected string $file = '@config/.env';

    public function load(): static
    {
        $file = Path::resolve($this->file);

        if (!str_contains($file, '://') && !is_file($file)) {
            throw new FileNotFoundException('The .env file could not be found at "{file}".', ['file' => $file]);
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES);

        $count = count($lines);
        /** @noinspection ForeachInvariantsInspection */
        for ($i = 0; $i < $count; $i++) {
            $line = trim($lines[$i]);

            if ($line === '' || $line[0] === '#') {
                continue;
            }

            $parts = explode('=', $line, 2);

            if (count($parts) !== 2) {
                throw new InvalidValueException('The .env file line "{line}" is invalid. Expected format: "KEY=VALUE".', ['line' => $line]);
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
                            throw new InvalidValueException(
                                'reference variable "{ref_name}" does not exist: {value}', ['ref_name' => $ref_name, 'value' => $value]
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
                throw new InvalidArgumentException('The key "{key}" does not exist in the .env file.', ['key' => $key]);
            }
            return $default;
        }

        if (is_array($default)) {
            if (is_array($value)) {
                return $value;
            } elseif ($value !== '' && $value[0] === '{') {
                if (is_array($r = json_parse($value))) {
                    return $r;
                } else {
                    throw new InvalidValueException('The value for key "{key}" is not a valid JSON array format.', ['key' => $key]);
                }
            } else {
                return preg_split('#[\s,]+#', $value, -1, PREG_SPLIT_NO_EMPTY);
            }
        } elseif (is_int($default)) {
            return (int)$value;
        } elseif (is_float($default)) {
            return (float)$value;
        } elseif (is_bool($default)) {
            if (is_bool($value)) {
                return $value;
            } elseif (in_array(strtolower($value), ['1', 'on', 'true'], true)) {
                return true;
            } elseif (in_array(strtolower($value), ['0', 'off', 'false'], true)) {
                return false;
            } else {
                throw new InvalidArgumentException('The value "{value}" for key "{key}" is not a valid boolean value.', ['key' => $key, 'value' => $value]);
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
