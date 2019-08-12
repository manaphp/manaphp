<?php

use ManaPHP\Di;
use ManaPHP\Exception\AbortException;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\JsonException;
use Swoole\Coroutine;

if (!function_exists('spl_object_id')) {
    function spl_object_id($object)
    {
        // https://github.com/akihiromukae/sample1/blob/1dc7b6e49684c882ef39476071179421fbd1e18e/vendor/phan/phan/src/spl_object_id.php
        $hash = spl_object_hash($object);
        return intval(PHP_INT_SIZE === 8 ? substr($hash, 1, 15) : substr($hash, 9, 7), 16);
    }
}

defined('JSON_THROW_ON_ERROR') or define('JSON_THROW_ON_ERROR', 0);

if (!function_exists('json_parse')) {
    /**
     * @param string $str
     *
     * @return mixed
     */
    function json_parse($str)
    {
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
        if (($str = json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR | $options, 16)) === false) {
            throw new JsonException('json_stringify failed');
        }

        return $str;
    }
}

if (!function_exists('di')) {
    /**
     * @param string $name
     * @param string $child
     *
     * @return mixed
     */
    function di($name = null, $child = null)
    {
        static $di;
        if (!$di) {
            $di = Di::getDefault();
        }

        if ($name === null || $name === 'di') {
            return $di;
        } elseif ($child) {
            return $di->has("{$child}_{$name}") ? $di->{"{$child}_{$name}"} : $di->{$child . ucfirst($name)};
        } else {
            return $di->$name;
        }
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
        return di('dotenv')->get($key, $default);
    }
}

if (!function_exists('param_get')) {
    /**
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    function param_get($name, $default = null)
    {
        static $configure;
        if (!$configure) {
            $configure = Di::getDefault()->getShared('configure');
        }

        return $configure->getParam($name, $default);
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
        static $logger;
        if (!$logger) {
            $logger = di('logger');
        }

        $logger->debug($message, $category);
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
        static $logger;
        if (!$logger) {
            $logger = di('logger');
        }

        $logger->info($message, $category);
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
        static $logger;
        if (!$logger) {
            $logger = di('logger');
        }

        $logger->warn($message, $category);
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
        static $logger;
        if (!$logger) {
            $logger = di('logger');
        }

        $logger->error($message, $category);
    }
}

if (!function_exists('log_fatal')) {
    /**
     * @param string|array $message
     * @param string       $category
     *
     * @return \ManaPHP\LoggerInterface
     */
    function log_fatal($message, $category = null)
    {
        static $logger;
        if (!$logger) {
            $logger = di('logger');
        }

        return $logger->fatal($message, $category);
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
        return $path ? di('alias')->resolve($path) : di('alias')->get();
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
        return di('jwt')->encode($claims, $ttl, $scope);
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
        return di('jwt')->decode($token, $scope, $verify);
    }
}

if (!function_exists('input')) {
    /**
     * @param string $name
     * @param mixed  $defaultOrRules
     *
     * @return mixed
     */
    function input($name = null, $defaultOrRules = null)
    {
        static $request;
        if (!$request) {
            $request = di('request');
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
        return di('request')->getClientIp();
    }
}

if (!function_exists('curl')) {
    /**
     * @param string          $type
     * @param string|array    $url
     * @param string|array    $body
     * @param array|string    $headers
     * @param array|int|float $options
     *
     * @return \ManaPHP\Http\Client\Response
     */
    function curl($type, $url, $body = null, $headers = [], $options = [])
    {
        return di('httpClient')->request($type, $url, $body, $headers, $options);
    }
}

if (!function_exists('curl_get')) {
    /**
     * @param string|array    $url
     * @param array|string    $headers
     * @param array|int|float $options
     *
     * @return \ManaPHP\Http\Client\Response
     */
    function curl_get($url, $headers = [], $options = [])
    {
        return di('httpClient')->get($url, $headers, $options);
    }
}

if (!function_exists('curl_post')) {
    /**
     * @param string|array    $url
     * @param string|array    $body
     * @param array|string    $headers
     * @param array|int|float $options
     *
     * @return \ManaPHP\Http\Client\Response
     */
    function curl_post($url, $body = null, $headers = [], $options = [])
    {
        return di('httpClient')->post($url, $body, $headers, $options);
    }
}

if (!function_exists('download')) {
    /**
     * @param string|array     $files
     * @param string|int|array $options
     *
     * @return string|array
     */
    function download($files, $options = [])
    {
        return di('httpClient')->download($files, $options);
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
        return di('restClient')->rest($type, $url, $body, $headers, $options);
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
        return di('restClient')->rest('GET', $url, null, $headers, $options);
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
        return di('restClient')->rest('POST', $url, $body, $headers, $options);
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
        return di('restClient')->rest('PUT', $url, $body, $headers, $options);
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
        return di('restClient')->rest('PATCH', $url, $body, $headers, $options);
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
        return di('restClient')->rest('DELETE', $url, null, $headers, $options);
    }
}

if (!function_exists('render')) {
    /**
     * @param string $file
     * @param array  $vars
     *
     * @return string
     */
    function render($file, $vars = [])
    {
        return di('renderer')->render($file, $vars);
    }
}

if (!function_exists('dd')) {
    function dd()
    {
        if (MANAPHP_COROUTINE_ENABLED) {
            /** @noinspection PhpUndefinedMethodInspection */
            $trace = Coroutine::getBackTrace(0, DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
        } else {
            $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
        }
        echo var_export($trace['file'] . ':' . $trace['line']), PHP_EOL;
        foreach (func_get_args() as $arg) {
            echo var_export($arg), PHP_EOL;
        }
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
        if (preg_match('#^([\d.]+)([smhd]?)$#', $str, $match)) {
            $units = ['' => 1, 's' => 1, 'm' => 60, 'h' => 3600, 'd' => 86400];
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
        return di('translator')->translate($id, $bind);
    }
}

if (!function_exists('str_starts_with')) {
    /**
     * @param string $haystack
     * @param string $needle
     * @param int    $offset
     *
     * @return bool
     */
    function str_starts_with($haystack, $needle, $offset = 0)
    {
        return strpos($haystack, $needle, $offset) === 0;
    }
}

if (!function_exists('mbstr_starts_with')) {
    /**
     * @param string $haystack
     * @param string $needle
     * @param int    $offset
     *
     * @return bool
     */
    function mbstr_starts_with($haystack, $needle, $offset = 0)
    {
        return mb_strpos($haystack, $needle, $offset) === 0;
    }
}

if (!function_exists('str_ends_with')) {
    /**
     * @param string $haystack
     * @param string $needle
     *
     * @return bool
     */
    function str_ends_with($haystack, $needle)
    {
        $lh = strlen($haystack);
        $ln = strlen($needle);
        if ($lh < $ln) {
            return false;
        }

        return substr_compare($haystack, $needle, $lh - $ln, $ln) === 0;
    }
}

if (!function_exists('mbstr_ends_with')) {
    /**
     * @param string $haystack
     * @param string $needle
     *
     * @return bool
     */
    function mbstr_ends_with($haystack, $needle)
    {
        $lh = mb_strlen($haystack);
        $ln = mb_strlen($needle);
        if ($lh < $ln) {
            return false;
        }

        return mb_substr($haystack, $lh - $ln) === $needle;
    }
}

if (!function_exists('str_contains')) {
    /**
     * @param string       $haystack
     * @param string|array $needle
     *
     * @return bool
     */
    function str_contains($haystack, $needle)
    {
        if (is_array($needle)) {
            foreach ($needle as $n) {
                if (strpos($haystack, $n) !== false) {
                    return true;
                }
            }
            return false;
        } else {
            return strpos($haystack, $needle) !== false;
        }
    }
}

if (!function_exists('mbstr_contains')) {
    /**
     * @param string       $haystack
     * @param string|array $needle
     *
     * @return bool
     */
    function mbstr_contains($haystack, $needle)
    {
        if (is_array($needle)) {
            foreach ($needle as $n) {
                if (mb_strpos($haystack, $n) !== false) {
                    return true;
                }
            }
            return false;
        } else {
            return mb_strpos($haystack, $needle) !== false;
        }
    }
}

if (!function_exists('array_unique_column')) {
    /**
     * @param array  $input
     * @param string $field_key
     * @param int    $sort
     *
     * @return array
     */
    function array_unique_column($input, $field_key, $sort = SORT_REGULAR)
    {
        $values = [];
        foreach ($input as $item) {
            $value = is_array($item) ? $item[$field_key] : $item->$field_key;
            if (!in_array($value, $values, true)) {
                $values[] = $value;
            }
        }

        if ($sort !== null) {
            sort($values, $sort);
        }

        return $values;
    }
}

if (!function_exists('array_only')) {
    /**
     * @param array $ar
     * @param array $keys
     *
     * @return array
     */
    function array_only($ar, $keys)
    {
        return array_intersect_key($ar, array_fill_keys($keys, null));
    }
}

if (!function_exists('array_except')) {
    /**
     * @param array $ar
     * @param array $keys
     *
     * @return array
     */
    function array_except($ar, $keys)
    {
        return array_diff_key($ar, array_fill_keys($keys, null));
    }
}

if (!function_exists('array_dot')) {
    /**
     * @param array  $ar
     * @param string $prepend
     *
     * @return array
     */
    function array_dot($ar, $prepend = '')
    {
        $r = [];

        foreach ($ar as $key => $value) {
            if (is_array($value) && $value) {
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $r = array_merge($r, array_dot($value, $prepend . $key . '.'));
            } else {
                $r[$prepend . $key] = $value;
            }
        }
        return $r;
    }
}

if (!function_exists('array_get')) {
    /**
     * @param array  $ar
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    function array_get($ar, $key, $default = null)
    {
        if (!$key) {
            return $ar;
        }

        if (($pos = strrpos($key, '.')) === false) {
            return $ar[$key] ?? null;
        }

        $t = $ar;
        foreach (explode('.', substr($key, 0, $pos)) as $segment) {
            if (!isset($t[$segment]) || !is_array($t[$segment])) {
                return $default;
            }
            $t = $t[$segment];
        }

        $last = substr($key, $pos + 1);
        return $t[$last] ?? $default;
    }
}

if (!function_exists('array_indexby')) {
    /**
     * @param array  $ar
     * @param string $key
     *
     * @return array
     */
    function array_indexby($ar, $key)
    {
        if (!is_array($ar)) {
            return $ar;
        }

        $r = [];
        foreach ($ar as $v) {
            $r[is_object($v) ? $v->$key : $v[$key]] = $v;
        }

        return $r;
    }
}

if (!function_exists('array_groupby')) {
    /**
     * @param array  $ar
     * @param string $key
     *
     * @return array
     */
    function array_groupby($ar, $key)
    {
        $r = [];

        foreach ($ar as $value) {
            $kv = is_object($value) ? $value->$key : $value[$value];
            $r[$kv][] = $value;
        }

        return $r;
    }
}

if (!function_exists('array_trim')) {
    /**
     * @param array $ar
     * @param bool  $removeEmpty
     *
     * @return array
     */
    function array_trim($ar, $removeEmpty = true)
    {
        foreach ($ar as $k => $v) {
            if (is_string($v)) {
                if ($v === '') {
                    if ($removeEmpty) {
                        unset($ar[$k]);
                    }
                } else {
                    $v = trim($v);
                    if ($v === '' && $removeEmpty) {
                        unset($ar[$k]);
                    } else {
                        $ar[$k] = $v;
                    }
                }
            } elseif (is_array($v)) {
                $ar[$k] = array_trim($v, $removeEmpty);
            }
        }

        return $ar;
    }
}

if (!function_exists('collection_sort')) {
    /**
     * @param array        $ar
     * @param string|array $sort
     *
     * @return array
     */
    function collection_sort(&$ar, $sort)
    {
        if (is_string($sort)) {
            $ref = array_column($ar, $sort);
            array_multisort($ref, SORT_ASC, $ar);
        } else {
            $params = [];
            foreach ((array)$sort as $k => $v) {
                if (is_int($k)) {
                    $params[] = array_column($ar, $v);
                } else {
                    $params[] = array_column($ar, $k);
                    $params[] = $v;
                }
            }
            $params[] = &$ar;
            /** @noinspection ArgumentUnpackingCanBeUsedInspection */
            /** @noinspection SpellCheckingInspection */
            call_user_func_array('array_multisort', $params);
        }

        return $ar;
    }
}