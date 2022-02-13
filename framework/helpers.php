<?php
declare(strict_types=1);

use ManaPHP\AliasInterface;
use ManaPHP\ConfigInterface;
use ManaPHP\Di\ContainerInterface;
use ManaPHP\Exception\AbortException;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\JsonException;

if (!function_exists('json_parse')) {
    function json_parse(string $str): mixed
    {
        /** @noinspection NotOptimalIfConditionsInspection */
        if (($json = json_decode($str, true, 16, JSON_THROW_ON_ERROR)) === null && $str !== 'null') {
            throw new JsonException('json_parse failed: ' . $str);
        }

        return $json;
    }
}

if (!function_exists('json_stringify')) {
    function json_stringify(mixed $json, int $options = 0): string
    {
        $options |= JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE;

        /** @noinspection JsonEncodingApiUsageInspection */
        if (($str = json_encode($json, $options, 16)) === false) {
            throw new JsonException('json_stringify failed');
        }

        return $str;
    }
}

if (!function_exists('xml_decode')) {
    function xml_decode(string $xml): ?array
    {
        if (($ret = @simplexml_load_string($xml, null, LIBXML_NOCDATA | LIBXML_NOBLANKS)) === false) {
            return null;
        }

        $ret = (array)$ret;

        foreach ($ret as $value) {
            if (!is_scalar($value) && $value !== null) {
                /** @noinspection JsonEncodingApiUsageInspection */
                return json_decode(json_encode($ret), true);
            }
        }

        return $ret;
    }
}

if (!function_exists('container')) {
    function container(?string $name = null): mixed
    {
        /** @var ContainerInterface $container */
        $container = $GLOBALS['ManaPHP\Di\ContainerInterface'];
        return $name === null ? $container : $container->get($name);
    }
}

if (!function_exists('env')) {
    function env(?string $key = null, mixed $default = null): mixed
    {
        return container(\ManaPHP\EnvInterface::class)->get($key, $default);
    }
}

if (!function_exists('config_get')) {
    function config_get(string $name, mixed $default = null): mixed
    {
        return container(ConfigInterface::class)->get($name, $default);
    }
}

if (!function_exists('dd')) {
    function dd(mixed $message): void
    {
        container(\ManaPHP\Debugging\DataDumpInterface::class)->output($message);
    }
}

if (!function_exists('path')) {
    function path(string $path): string
    {
        return container(AliasInterface::class)->resolve($path);
    }
}

if (!function_exists('jwt_encode')) {
    function jwt_encode(array $claims, int $ttl, string $scope): string
    {
        return container(\ManaPHP\Token\ScopedJwtInterface::class)->encode($claims, $ttl, $scope);
    }
}

if (!function_exists('jwt_decode')) {
    function jwt_decode(string $token, string $scope, bool $verify = true): array
    {
        return container(\ManaPHP\Token\ScopedJwtInterface::class)->decode($token, $scope, $verify);
    }
}

if (!function_exists('jwt_verify')) {
    function jwt_verify(string $token, string $scope): void
    {
        container(\ManaPHP\Token\ScopedJwt::class)->verify($token, $scope);
    }
}

if (!function_exists('input')) {
    /**
     * @param ?string $name
     * @param mixed   $defaultOrRules =\PHPSTORM_META\validator_rule()
     *
     * @return mixed
     */
    function input(?string $name = null, mixed $defaultOrRules = null): mixed
    {
        static $request;
        if (!$request) {
            $request = container(\ManaPHP\Http\RequestInterface::class);
        }

        if ($defaultOrRules && is_array($defaultOrRules)) {
            $value = $request->get($name, $defaultOrRules['default'] ?? null);

            return container(\ManaPHP\Validating\ValidatorInterface::class)->validateValue(
                $name, $value, $defaultOrRules
            );
        } else {
            return $request->get($name, $defaultOrRules);
        }
    }
}

if (!function_exists('abort')) {
    function abort(): void
    {
        throw new AbortException();
    }
}

if (!function_exists('seconds')) {
    function seconds(string $str): int
    {
        if (preg_match('#^([\d.]+)([smhdMy]?)$#', $str, $match)) {
            $units = ['' => 1, 's' => 1, 'm' => 60, 'h' => 3600, 'd' => 86400, 'M' => 2592000, 'y' => 31536000];
            return $match[1] * $units[$match[2]];
        } elseif (($r = strtotime($str, 0)) !== false) {
            return $r;
        } else {
            throw new InvalidValueException(['`:str` string is not a valid seconds expression', 'str' => $str]);
        }
    }
}

if (!function_exists('e')) {
    function e(string $value, bool $doubleEncode = true): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', $doubleEncode);
    }
}

if (!function_exists('t')) {
    function t(string $id, array $bind = []): string
    {
        return container(\ManaPHP\I18n\TranslatorInterface::class)->translate($id, $bind);
    }
}

if (!function_exists('base_url')) {
    function base_url(): string
    {
        return container(\ManaPHP\AliasInterface::class)->get('@web');
    }
}

if (!function_exists('console_log')) {
    function console_log(string $level, mixed $message): void
    {
        if (is_array($message)) {
            $message = sprintf(...$message);
        }
        echo sprintf('[%s][%s]: ', date('c'), $level), $message, PHP_EOL;
    }
}

if (!function_exists('apcu_remember')) {
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