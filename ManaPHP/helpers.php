<?php

use ManaPHP\Di;

if (PHP_VERSION_ID < 70000) {
    require_once __DIR__ . '/polyfill.php';
}

if (!function_exists('spl_object_id')) {
    function spl_object_id($object)
    {
        // https://github.com/akihiromukae/sample1/blob/1dc7b6e49684c882ef39476071179421fbd1e18e/vendor/phan/phan/src/spl_object_id.php
        $hash = spl_object_hash($object);
        return intval(PHP_INT_SIZE === 8 ? substr($hash, 1, 15) : substr($hash, 9, 7), 16);
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
        return Di::getDefault()->configure->getParam($name, $default);
    }
}

if (!function_exists('debug')) {
    /**
     * @param string|array $message
     * @param string       $category
     *
     * @return void
     */
    function debug($message, $category = null)
    {
        static $logger;
        if (!$logger) {
            $logger = di('logger');
        }

        $logger->debug($message, $category);
    }
}

if (!function_exists('info')) {
    /**
     * @param string|array $message
     * @param string       $category
     *
     * @return void
     */
    function info($message, $category = null)
    {
        static $logger;
        if (!$logger) {
            $logger = di('logger');
        }

        $logger->info($message, $category);
    }
}

if (!function_exists('warn')) {
    /**
     * @param string|array $message
     * @param string       $category
     *
     * @return void
     */
    function warn($message, $category = null)
    {
        static $logger;
        if (!$logger) {
            $logger = di('logger');
        }

        $logger->warn($message, $category);
    }
}

if (!function_exists('error')) {
    /**
     * @param string|array $message
     * @param string       $category
     *
     * @return void
     */
    function error($message, $category = null)
    {
        static $logger;
        if (!$logger) {
            $logger = di('logger');
        }

        $logger->error($message, $category);
    }
}

if (!function_exists('fatal')) {
    /**
     * @param string|array $message
     * @param string       $category
     *
     * @return \ManaPHP\LoggerInterface
     */
    function fatal($message, $category = null)
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

if (!function_exists('abort')) {
    /**
     * @param string|array $message
     * @param int          $code
     */
    function abort($message = null, $code = 200)
    {
        if ($message) {
            if (is_string($message)) {
                di('response')->setStatus($code)->setContent($message);
            } else {
                di('response')->setStatus($code)->setJsonContent($message);
            }
        } else {
            di('response')->setStatus($code);
        }

        throw new \ManaPHP\Exception\AbortException();
    }
}

if (!function_exists('jwt_encode')) {
    /**
     * @param array  $claims
     * @param int    $ttl
     * @param string $key
     *
     * @return string
     */
    function jwt_encode($claims, $ttl, $key = null)
    {
        if (!$key && !isset($claims['scope'])) {
            throw new \ManaPHP\Exception\MisuseException('neither key nor scope field exists');
        }
        $jwt = new ManaPHP\Identity\Adapter\Jwt(['key' => $key ?: di('crypt')->getDerivedKey('jwt:' . $claims['scope'])]);
        return $jwt->encode($claims, $ttl);
    }
}

if (!function_exists('jwt_decode')) {
    /**
     * @param string $token
     * @param string $scope
     * @param string $key
     *
     * @return array
     */
    function jwt_decode($token, $scope, $key = null)
    {
        $jwt = new ManaPHP\Identity\Adapter\Jwt();

        $claims = $jwt->decode($token, false);
        if ($scope) {
            if (!isset($claims['scope'])) {
                throw new \ManaPHP\Identity\InvalidFormatException('Jwt claims missing scope field');
            }
            if ($scope !== $claims['scope']) {
                throw new \ManaPHP\Identity\InvalidFormatException(['Jwt `:1` scope is not wanted `:2`', $claims['scope'], $scope]);
            }
        } else {
            if (!$key) {
                throw new \ManaPHP\Identity\InvalidFormatException('Jwt claims missing scope field');
            }
        }
        $jwt->setKey($key ?: di('crypt')->getDerivedKey('jwt:' . $scope));
        $jwt->verify($token);

        return $claims;
    }
}

if (!function_exists('jwt_get_claim')) {
    /**
     * @param string $token
     * @param string $name
     * @param mixed  $default
     *
     * @return array|string
     */
    function jwt_get_claim($token, $name = null, $default = null)
    {
        $claims = (new ManaPHP\Identity\Adapter\Jwt())->decode($token, false);
        if (!$name) {
            return $claims;
        } elseif (isset($name)) {
            return $claims[$name];
        } elseif ($default === null) {
            throw new \ManaPHP\Exception\AuthenticationException(['`claim` claim is not exists in token', 'claim' => $name]);
        } else {
            return $default;
        }
    }
}

if (!function_exists('input')) {
    /**
     * @param string $name
     * @param mixed  $default
     *
     * @return string|array
     */
    function input($name = null, $default = null)
    {
        static $request;
        if (!$request) {
            $request = di('request');
        }

        if ($name === 'id') {
            $params = di('dispatcher')->getParams();
            if (count($params) === 1 && isset($params[0])) {
                return $params[0];
            }
        }

        if (($value = $request->getInput($name, null, $default)) === null) {
            if ($default === null) {
                throw new \ManaPHP\Exception\MissingRequiredFieldsException($name);
            } else {
                return $default;
            }
        } else {
            return (is_array($value) && is_scalar($default)) ? $default : $value;
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

if (!function_exists('elapsed')) {
    /**
     * @param float|string $previous
     * @param int          $precision
     *
     * @return float
     */
    function elapsed($previous = null, $precision = 3)
    {
        static $stack;
        if (is_float($previous)) {
            return round(microtime(true) - $previous, 3);
        } elseif (is_string($previous)) {
            $key = $previous;
        } else {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];
            $key = $backtrace['class'] . $backtrace['function'];
        }

        if (!isset($stack[$key]) || count($stack[$key]) % 2 === 0) {
            $stack[$key][] = microtime(true);
            return null;
        } else {
            $prev = array_pop($stack[$key]);
            return round(microtime(true) - $prev, $precision);
        }
    }
}

if (!function_exists('dd')) {
    function dd()
    {
        foreach (func_get_args() as $arg) {
            var_dump($arg);
        }
        exit(1);
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
        if (($r = strtotime($str, 0)) !== false) {
            return $r;
        } else {
            throw new \ManaPHP\Exception\InvalidValueException(['`:str` string is not a valid seconds expression', 'str' => $str]);
        }
    }
}

if (!function_exists('json')) {
    /**
     * @param array|string $data
     *
     * @return array|string
     */
    function json($data)
    {
        if (is_string($data)) {
            if (!is_array($r = json_decode($data, true))) {
                throw new \ManaPHP\Exception\InvalidJsonException(['`:data` data', 'data' => $data]);
            } else {
                return $r;
            }
        } elseif (is_array($data)) {
            return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } else {
            throw new \ManaPHP\Exception\UnexpectedValueException(['`:data`', 'data' => $data]);
        }
    }
}

if (!function_exists('transaction')) {
    /**
     * @param callable $work
     * @param string   $service
     *
     * @return true|string
     */
    function transaction($work, $service = 'db')
    {
        try {
            /** @var \ManaPHP\DbInterface $db */
            $db = di($service);
            $db->begin();
            $work();
            $db->commit();
        } catch (\Exception $exception) {
            /** @noinspection UnSafeIsSetOverArrayInspection */
            isset($db) && $db->rollback();
            error($exception);
            return $exception->getMessage();
        } catch (\Error $error) {
            /** @noinspection UnSafeIsSetOverArrayInspection */
            isset($db) && $db->rollback();
            error($error);
            return $error->getMessage();
        }
        return true;
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

if (!function_exists('tap')) {
    /** @noinspection AutoloadingIssuesInspection */

    class _manaphp_tap_proxy
    {
        public $target;

        public function __construct($target)
        {
            $this->target = $target;
        }

        public function __call($method, $arguments)
        {
            call_user_func([$this->target, $method], $arguments);

            return $this->target;
        }
    }

    /**
     * @param mixed         $value
     * @param callable|null $callback
     *
     * @return mixed
     */
    function tap($value, $callback = null)
    {
        if ($callback === null) {
            return new _manaphp_tap_proxy($value);
        } else {
            $callback($value);
            return $value;
        }
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

if (!function_exists('array_field')) {
    /**
     * @param array  $input
     * @param string $field_key
     *
     * @return array
     */
    function array_field($input, $field_key)
    {
        if (PHP_MAJOR_VERSION >= 7) {
            return array_column($input, $field_key);
        } else {
            $values = [];
            foreach ($input as $item) {
                $values[] = is_array($item) ? $item[$field_key] : $item->$field_key;
            }

            return $values;
        }
    }
}

if (!function_exists('array_ufield')) {
    /**
     * @param array  $input
     * @param string $field_key
     * @param int    $sort
     *
     * @return array
     */
    function array_ufield($input, $field_key, $sort = SORT_REGULAR)
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
            return isset($ar[$key]) ? $ar[$key] : null;
        }

        $t = $ar;
        foreach (explode('.', substr($key, 0, $pos)) as $segment) {
            if (!isset($t[$segment]) || !is_array($t[$segment])) {
                return $default;
            }
            $t = $t[$segment];
        }

        $last = substr($key, $pos + 1);
        return isset($t[$last]) ? $t[$last] : $default;
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
            /** @noinspection PassingByReferenceCorrectnessInspection */
            array_multisort(array_field($ar, $sort), SORT_ASC, $ar);
        } else {
            $params = [];
            foreach ((array)$sort as $k => $v) {
                if (is_int($k)) {
                    $params[] = array_field($ar, $v);
                } else {
                    $params[] = array_field($ar, $k);
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