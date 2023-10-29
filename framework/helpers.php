<?php
declare(strict_types=1);

use ManaPHP\AliasInterface;
use ManaPHP\Debugging\DataDumpInterface;
use ManaPHP\Di\ConfigInterface;
use ManaPHP\EnvInterface;
use ManaPHP\Exception\AbortException;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\JsonException;
use ManaPHP\Helper\Container;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\RouterInterface;
use ManaPHP\I18n\TranslatorInterface;
use ManaPHP\Token\ScopedJwtInterface;
use ManaPHP\Validating\ValidatorInterface;

if (!\function_exists('json_parse')) {
    function json_parse(string $str): mixed
    {
        /** @noinspection NotOptimalIfConditionsInspection */
        if (($json = json_decode($str, true, 16, JSON_THROW_ON_ERROR)) === null && $str !== 'null') {
            throw new JsonException('json_parse failed: ' . $str);
        }

        return $json;
    }
}

if (!\function_exists('json_stringify')) {
    function json_stringify(mixed $json, int $options = 0): string
    {
        $options |= JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE;

        /** @noinspection JsonEncodingApiUsageInspection */
        if (($str = json_encode($json, $options, 16)) === false) {
            throw new JsonException('json_stringify failed: ' . json_last_error_msg());
        }

        return $str;
    }
}

if (!\function_exists('xml_decode')) {
    function xml_decode(string $xml): ?array
    {
        if (($ret = @simplexml_load_string($xml, null, LIBXML_NOCDATA | LIBXML_NOBLANKS)) === false) {
            return null;
        }

        $ret = (array)$ret;

        foreach ($ret as $value) {
            if (!\is_scalar($value) && $value !== null) {
                /** @noinspection JsonEncodingApiUsageInspection */
                return json_decode(json_encode($ret), true);
            }
        }

        return $ret;
    }
}

if (!\function_exists('container')) {
    function container(string $id): mixed
    {
        return Container::get($id);
    }
}

if (!\function_exists('make')) {
    function make(string $name, array $parameters = []): mixed
    {
        return Container::make($name, $parameters);
    }
}

if (!\function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return Container::get(EnvInterface::class)->get($key, $default);
    }
}

if (!\function_exists('config_get')) {
    function config_get(string $name, mixed $default = null): mixed
    {
        return Container::get(ConfigInterface::class)->get($name, $default);
    }
}

if (!\function_exists('dd')) {
    function dd(mixed $message): void
    {
        Container::get(DataDumpInterface::class)->output($message);
    }
}

if (!\function_exists('path')) {
    function path(string $path): string
    {
        return Container::get(AliasInterface::class)->resolve($path);
    }
}

if (!\function_exists('jwt_encode')) {
    function jwt_encode(array $claims, int $ttl, string $scope): string
    {
        return Container::get(ScopedJwtInterface::class)->encode($claims, $ttl, $scope);
    }
}

if (!\function_exists('jwt_decode')) {
    function jwt_decode(string $token, string $scope, bool $verify = true): array
    {
        return Container::get(ScopedJwtInterface::class)->decode($token, $scope, $verify);
    }
}

if (!\function_exists('jwt_verify')) {
    function jwt_verify(string $token, string $scope): void
    {
        Container::get(ScopedJwtInterface::class)->verify($token, $scope);
    }
}

if (!\function_exists('input')) {
    /**
     * @param string $name
     * @param mixed  $defaultOrRules =\PHPSTORM_META\validator_rule()
     *
     * @return mixed
     */
    function input(string $name, mixed $defaultOrRules = null): mixed
    {
        $request = Container::get(RequestInterface::class);

        if ($defaultOrRules === null) {
            $value = $request->get($name);
        } elseif (\is_array($defaultOrRules)) {
            $value = $request->get($name, $defaultOrRules['default'] ?? null);
        } else {
            return $request->get($name, $defaultOrRules);
        }

        return $value ?? Container::get(ValidatorInterface::class)->validateValue($name, null, 'required');
    }
}

if (!\function_exists('abort')) {
    function abort(): void
    {
        throw new AbortException();
    }
}

if (!\function_exists('seconds')) {
    function seconds(string $str): int
    {
        if (preg_match('#^([\d.]+)([smhdMy]?)$#', $str, $match)) {
            $units = ['' => 1, 's' => 1, 'm' => 60, 'h' => 3600, 'd' => 86400, 'M' => 2592000, 'y' => 31536000];
            return $match[1] * $units[$match[2]];
        } elseif (($r = strtotime($str, 0)) !== false) {
            return $r;
        } else {
            throw new InvalidValueException(['`{str}` string is not a valid seconds expression', 'str' => $str]);
        }
    }
}

if (!\function_exists('e')) {
    function e(string $value, bool $doubleEncode = true): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', $doubleEncode);
    }
}

if (!\function_exists('t')) {
    function t(string $id, array $bind = []): string
    {
        return Container::get(TranslatorInterface::class)->translate($id, $bind);
    }
}

if (!\function_exists('base_url')) {
    function base_url(): string
    {
        return Container::get(RouterInterface::class)->getPrefix();
    }
}

if (!\function_exists('console_log')) {
    function console_log(string $level, mixed $message): void
    {
        if (\is_array($message)) {
            $message = sprintf(...$message);
        }
        echo sprintf('[%s][%s]: ', date('c'), $level), $message, PHP_EOL;
    }
}

if (!\function_exists('apcu_remember')) {
    function apcu_remember(string $key, int $ttl, callable $callback): mixed
    {
        $value = apcu_fetch($key, $success);
        if (!$success) {
            $value = $callback();
            apcu_store($key, $value, $ttl);
        }

        return $value;
    }
}