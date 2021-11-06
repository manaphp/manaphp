<?php

use ManaPHP\Di\ContainerInterface;
use ManaPHP\Exception\AbortException;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\JsonException;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\ConfigInterface;
use ManaPHP\AliasInterface;

if (!function_exists('spl_object_id')) {
    function spl_object_id($object)
    {
        // https://github.com/akihiromukae/sample1/blob/1dc7b6e49684c882ef39476071179421fbd1e18e/vendor/phan/phan/src/spl_object_id.php
        $hash = spl_object_hash($object);
        return intval(PHP_INT_SIZE === 8 ? substr($hash, 1, 15) : substr($hash, 9, 7), 16);
    }
}

if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle)
    {
        return strpos($haystack, $needle) !== false;
    }
}

if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle)
    {
        return strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle)
    {
        $haystack_len = strlen($haystack);
        $needle_len = strlen($needle);
        return $haystack_len >= $needle_len && substr_compare($haystack, $needle, -$needle_len) === 0;
    }
}

if (!function_exists('array_key_first')) {
    function array_key_first($ar)
    {
        /** @noinspection LoopWhichDoesNotLoopInspection */
        foreach ($ar as $key => $unused) {
            return $key;
        }
        return null;
    }
}

if (!function_exists('array_key_last')) {
    function array_key_last($ar)
    {
        return count($ar) === 0 ? null : key(array_slice($ar, -1, 1, true));
    }
}

defined('JSON_THROW_ON_ERROR') or define('JSON_THROW_ON_ERROR', 0);
defined('JSON_INVALID_UTF8_SUBSTITUTE') or define('JSON_INVALID_UTF8_SUBSTITUTE', 0);

if (!function_exists('json_parse')) {
    /**
     * @param string $str
     *
     * @return mixed
     */
    function json_parse($str)
    {
        /** @noinspection NotOptimalIfConditionsInspection */
        if (($json = json_decode($str, true, 16, JSON_THROW_ON_ERROR)) === null && $str !== 'null') {
            throw new JsonException('json_parse failed: ' . $str);
        }

        return $json;
    }
}

if (!function_exists('json_stringify')) {
    /**
     * @param mixed $json
     * @param int   $options
     *
     * @return string
     */
    function json_stringify($json, $options = 0)
    {
        $options |= JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE;

        if (($str = json_encode($json, $options, 16)) === false) {
            throw new JsonException('json_stringify failed');
        }

        return $str;
    }
}

if (!function_exists('xml_decode')) {
    /**
     * @param string $xml
     *
     * @return array|null
     */
    function xml_decode($xml)
    {
        if (($ret = @simplexml_load_string($xml, null, LIBXML_NOCDATA | LIBXML_NOBLANKS)) === false) {
            return null;
        }

        $ret = (array)$ret;

        foreach ($ret as $value) {
            if (!is_scalar($value) && $value !== null) {
                return json_decode(json_encode($ret), true);
            }
        }

        return $ret;
    }
}

if (!function_exists('container')) {
    /**
     * @param string $name
     *
     * @return mixed
     */
    function container($name = null)
    {
        /** @var ContainerInterface $container */
        $container = $GLOBALS['ManaPHP\Di\ContainerInterface'];
        return $name === null ? $container : $container->get($name);
    }
}

if (!function_exists('env')) {
    /**
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    function env($key = null, $default = null)
    {
        return container(\ManaPHP\DotenvInterface::class)->get($key, $default);
    }
}

if (!function_exists('config_get')) {
    /**
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    function config_get($name, $default = null)
    {
        return container(ConfigInterface::class)->get($name, $default);
    }
}

if (!function_exists('log_debug')) {
    /**
     * @param string|array $message
     * @param string       $category
     *
     * @return void
     */
    function log_debug($message, $category = null)
    {
        container(\ManaPHP\Logging\LoggerInterface::class)->debug($message, $category);
    }
}

if (!function_exists('log_info')) {
    /**
     * @param string|array $message
     * @param string       $category
     *
     * @return void
     */
    function log_info($message, $category = null)
    {
        container(\ManaPHP\Logging\LoggerInterface::class)->info($message, $category);
    }
}

if (!function_exists('log_warn')) {
    /**
     * @param string|array $message
     * @param string       $category
     *
     * @return void
     */
    function log_warn($message, $category = null)
    {
        container(\ManaPHP\Logging\LoggerInterface::class)->warn($message, $category);
    }
}

if (!function_exists('log_error')) {
    /**
     * @param string|array $message
     * @param string       $category
     *
     * @return void
     */
    function log_error($message, $category = null)
    {
        container(\ManaPHP\Logging\LoggerInterface::class)->error($message, $category);
    }
}

if (!function_exists('log_fatal')) {
    /**
     * @param string|array $message
     * @param string       $category
     *
     * @return void
     */
    function log_fatal($message, $category = null)
    {
        container(\ManaPHP\Logging\LoggerInterface::class)->fatal($message, $category);
    }
}

if (!function_exists('dd')) {
    /**
     * @param mixed $message
     *
     * @return void
     */
    function dd($message)
    {
        container(\ManaPHP\Debugging\DataDumpInterface::class)->output($message);
    }
}

if (!function_exists('path')) {
    /**
     * @param string $path
     *
     * @return string
     */
    function path($path)
    {
        return $path ? container(AliasInterface::class)->resolve($path) : container(AliasInterface::class)->get();
    }
}

if (!function_exists('jwt_encode')) {
    /**
     * @param array  $claims
     * @param int    $ttl
     * @param string $scope
     *
     * @return string
     */
    function jwt_encode($claims, $ttl, $scope)
    {
        return container(\ManaPHP\Token\ScopedJwtInterface::class)->encode($claims, $ttl, $scope);
    }
}

if (!function_exists('jwt_decode')) {
    /**
     * @param string $token
     * @param string $scope
     * @param bool   $verify
     *
     * @return array
     */
    function jwt_decode($token, $scope, $verify = true)
    {
        return container(\ManaPHP\Token\ScopedJwtInterface::class)->decode($token, $scope, $verify);
    }
}

if (!function_exists('jwt_verify')) {
    /**
     * @param string $token
     * @param string $scope
     *
     * @return void
     */
    function jwt_verify($token, $scope)
    {
        container(\ManaPHP\Token\ScopedJwt::class)->verify($token, $scope);
    }
}

if (!function_exists('input')) {
    /**
     * @param string $name
     * @param mixed  $defaultOrRules =\PHPSTORM_META\validator_rule()
     *
     * @return mixed
     */
    function input($name = null, $defaultOrRules = null)
    {
        static $request;
        if (!$request) {
            $request = container(\ManaPHP\Http\RequestInterface::class);
        }

        if ($defaultOrRules && is_array($defaultOrRules)) {
            $value = $request->get($name, $defaultOrRules['default'] ?? null);
            return $request->validator->validateValue($name, $value, $defaultOrRules);
        } else {
            return $request->get($name, $defaultOrRules);
        }
    }
}

if (!function_exists('client_ip')) {
    /**
     * @return string
     */
    function client_ip()
    {
        return container(\ManaPHP\Http\RequestInterface::class)->getClientIp();
    }
}

if (!function_exists('rest')) {
    /**
     * @param string          $type
     * @param string|array    $url
     * @param string|array    $body
     * @param array|string    $headers
     * @param array|int|float $options
     *
     * @return \ManaPHP\Http\Client\Response
     */
    function rest($type, $url, $body = null, $headers = [], $options = [])
    {
        return container(\ManaPHP\Rest\ClientInterface::class)->rest($type, $url, $body, $headers, $options);
    }
}

if (!function_exists('rest_get')) {
    /**
     * @param string|array    $url
     * @param array|string    $headers
     * @param array|int|float $options
     *
     * @return \ManaPHP\Http\Client\Response
     */
    function rest_get($url, $headers = [], $options = [])
    {
        return container(\ManaPHP\Rest\ClientInterface::class)->rest('GET', $url, null, $headers, $options);
    }
}

if (!function_exists('rest_post')) {
    /**
     * @param string|array    $url
     * @param string|array    $body
     * @param array|string    $headers
     * @param array|int|float $options
     *
     * @return \ManaPHP\Http\Client\Response
     */
    function rest_post($url, $body, $headers = [], $options = [])
    {
        return container(\ManaPHP\Rest\ClientInterface::class)->rest('POST', $url, $body, $headers, $options);
    }
}

if (!function_exists('rest_put')) {
    /**
     * @param string|array    $url
     * @param string|array    $body
     * @param array|string    $headers
     * @param array|int|float $options
     *
     * @return \ManaPHP\Http\Client\Response
     */
    function rest_put($url, $body, $headers = [], $options = [])
    {
        return container(\ManaPHP\Rest\ClientInterface::class)->rest('PUT', $url, $body, $headers, $options);
    }
}

if (!function_exists('rest_patch')) {
    /**
     * @param string|array    $url
     * @param string|array    $body
     * @param array|string    $headers
     * @param array|int|float $options
     *
     * @return \ManaPHP\Http\Client\Response
     */
    function rest_patch($url, $body, $headers = [], $options = [])
    {
        return container(\ManaPHP\Rest\ClientInterface::class)->rest('PATCH', $url, $body, $headers, $options);
    }
}

if (!function_exists('rest_delete')) {
    /**
     * @param string|array    $url
     * @param array|string    $headers
     * @param array|int|float $options
     *
     * @return \ManaPHP\Http\Client\Response
     */
    function rest_delete($url, $headers = [], $options = [])
    {
        return container(\ManaPHP\Rest\ClientInterface::class)->rest('DELETE', $url, null, $headers, $options);
    }
}

if (!function_exists('render_file')) {
    /**
     * @param string $file
     * @param array  $vars
     *
     * @return string
     */
    function render_file($file, $vars = [])
    {
        return container(\ManaPHP\Html\RendererInterface::class)->renderFile($file, $vars);
    }
}

if (!function_exists('abort')) {
    /**
     * @return void
     * @throws \ManaPHP\Exception\AbortException
     */
    function abort()
    {
        throw new AbortException();
    }
}

if (!function_exists('seconds')) {
    /**
     * @param string $str
     *
     * @return int
     */
    function seconds($str)
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
    /**
     * @param string $value
     * @param bool   $doubleEncode
     *
     * @return string
     */
    function e($value, $doubleEncode = true)
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', $doubleEncode);
    }
}

if (!function_exists('t')) {
    /**
     * @param string $id
     * @param array  $bind
     *
     * @return string
     */
    function t($id, $bind = [])
    {
        return container(\ManaPHP\I18n\TranslatorInterface::class)->translate($id, $bind);
    }
}

if (!function_exists('image_create')) {
    /**
     * @param string $file
     *
     * @return \ManaPHP\Imaging\ImageInterface
     */
    function image_create($file)
    {
        if (extension_loaded('imagick')) {
            return container('ManaPHP\Di\ContainerInterface')->make('ManaPHP\Imaging\Image\Adapter\Imagick', [$file]);
        } elseif (extension_loaded('gd')) {
            return container()->make('ManaPHP\Imaging\Image\Adapter\Gd', [$file]);
        } else {
            throw new NotSupportedException('neither `imagic` nor `gd` extension is loaded');
        }
    }
}

if (!function_exists('base_url')) {
    /**
     * @return string
     */
    function base_url()
    {
        return container(\ManaPHP\AliasInterface::class)->get('@web');
    }
}

if (!function_exists('console_log')) {
    /**
     * @param string $level
     * @param mixed  $message
     *
     * @return void
     */
    function console_log($level, $message)
    {
        if (is_array($message)) {
            $message = sprintf(...$message);
        }
        echo sprintf('[%s][%s]: ', date('c'), $level), $message, PHP_EOL;
    }
}

if (!function_exists('apcu_remember')) {
    /**
     * @param string   $key
     * @param int      $ttl
     * @param callable $callback
     *
     * @return mixed
     */
    function apcu_remember($key, $ttl, $callback)
    {
        $value = apcu_fetch($key, $success);
        if (!$success) {
            $value = $callback();
            apcu_store($key, $value, $ttl);
        }

        return $value;
    }
}