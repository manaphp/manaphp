<?php

use ManaPHP\Di;

if (!function_exists('di')) {
    /**
     * @param string $name
     * @param array  $params
     *
     * @return mixed
     */
    function di($name = null, $params = null)
    {
        static $di;
        if (!$di) {
            $di = Di::getDefault();
        }

        if ($name === null || $name === 'di') {
            return $di;
        } elseif ($params) {
            return $di->getInstance($name, $params);
        } else {
            return $di->getShared($name);
        }
    }
}

if (!function_exists('debug')) {
    /**
     * @param string|array $message
     * @param string       $category
     *
     * @return \ManaPHP\LoggerInterface
     */
    function debug($message, $category = null)
    {
        return di('logger')->debug($message, $category);
    }
}

if (!function_exists('info')) {
    /**
     * @param string|array $message
     * @param string       $category
     *
     * @return \ManaPHP\LoggerInterface
     */
    function info($message, $category = null)
    {
        return di('logger')->info($message, $category);
    }
}

if (!function_exists('warn')) {
    /**
     * @param string|array $message
     * @param string       $category
     *
     * @return \ManaPHP\LoggerInterface
     */
    function warn($message, $category = null)
    {
        return di('logger')->warn($message, $category);
    }
}

if (!function_exists('error')) {
    /**
     * @param string|array $message
     * @param string       $category
     *
     * @return \ManaPHP\LoggerInterface
     */
    function error($message, $category = null)
    {
        return di('logger')->error($message, $category);
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
        return di('logger')->fatal($message, $category);
    }
}

if (!function_exists('cache')) {
    /**
     * @param string                $name
     * @param false|\Closure|string $default
     * @param int                   $ttl
     *
     * @return bool|array|mixed|\ManaPHP\CacheInterface
     */
    function cache($name = null, $default = false, $ttl = null)
    {
        if ($ttl) {
            if ($default instanceof \Closure) {
                $value = di('cache')->get($name);
                if ($value !== false) {
                    return $value;
                } else {
                    di('cache')->set($name, $default(), $ttl);
                }
            } else {
                di('cache')->set($name, $default, $ttl);
            }
            return null;
        } else {
            if (!$name) {
                return di('cache');
            } elseif (strpos($name, ':') === false) {
                return di("${name}Cache");
            } else {
                $value = di('cache')->get($name);
                return $value === false ? $value : $default;
            }
        }
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
     * @param int          $code
     * @param string|array $message
     */
    function abort($code, $message = null)
    {
        if ($message) {
            if (is_string($message)) {
                di('response')->setStatus($code, $code === 200 ? 'OK' : 'Abort')->setContent($message);
            } else {
                di('response')->setStatus($code, $code === 200 ? 'OK' : 'Abort')->setJsonContent($message);
            }
            throw new \ManaPHP\ExitException('');
        }
    }
}

if (!function_exists('token')) {
    /**
     * @param string|array $data
     * @param int          $ttl
     *
     * @return array|false|string
     */
    function token($data = null, $ttl = null)
    {
        if ($ttl) {
            $data['exp'] = time() + $ttl;
            return di('authenticationToken')->encode($data);
        } else {
            return di('authenticationToken')->decode($data ?: di('request')->getAccessToken());
        }
    }
}

if (!function_exists('jwt')) {
    /**
     * @param string       $scope
     * @param string|array $data
     * @param int          $ttl
     *
     * @return string|array|false
     */
    function jwt($scope, $data, $ttl = null)
    {
        $jwt = di('ManaPHP\Authentication\Token\Adapter\Jwt', ['key' => di('crypt')->getDerivedKey("jwt:$scope")]);
        if ($ttl) {
            return $jwt->encode(array_merge(['scope' => $scope, 'exp' => time() + $ttl], $data));
        } else {
            $r = $jwt->decode($data);
            return !$r || !isset($r['scope']) || $r['scope'] !== $scope ? false : $r;
        }
    }
}

if (!function_exists('mwt')) {
    /**
     * @param string       $scope
     * @param string|array $data
     * @param int          $ttl
     *
     * @return string|array|false
     */
    function mwt($scope, $data, $ttl = null)
    {
        $mwt = di('ManaPHP\Authentication\Token\Adapter\Mwt', ['key' => di('crypt')->getDerivedKey("mwt:$scope")]);
        if ($ttl) {
            return $mwt->encode(array_merge(['scope' => $scope, 'exp' => time() + $ttl], $data));
        } else {
            $r = $mwt->decode($data);
            return !$r || !isset($r['scope']) || $r['scope'] !== $scope ? false : $r;
        }
    }
}

if (!function_exists('request')) {
    /**
     * @param string $name
     * @param mixed  $default
     *
     * @return string|array|\ManaPHP\Http\RequestInterface
     */
    function request($name = null, $default = null)
    {
        return $name === null ? di('request') : di('request')->get($name, false, $default);
    }
}

if (!function_exists('session')) {
    /**
     * @param string|array $data
     *
     * @return mixed|\ManaPHP\Http\SessionInterface
     */
    function session($data = null)
    {
        if ($data === null) {
            return di('session');
        } elseif (is_array($data)) {
            $session = di('session');
            foreach ((array)$data as $k => $v) {
                $session->set($k, $v);
            }
            return null;
        } else {
            return di('session')->get($data);
        }
    }
}

if (!function_exists('db')) {
    /**
     * @param string $name
     *
     * @return \ManaPHP\DbInterface
     */
    function db($name = null)
    {
        return di($name ? "${name}Db" : 'db');
    }
}

if (!function_exists('mongodb')) {
    /**
     * @param string $name
     *
     * @return \ManaPHP\MongodbInterface
     */
    function mongodb($name = null)
    {
        return di($name ? "${name}Mongodb" : 'mongodb');
    }
}

if (!function_exists('redis')) {
    /**
     * @param string $name
     *
     * @return \Redis
     */
    function redis($name = null)
    {
        return di($name ? "${name}Redis" : 'redis');
    }
}

if (!function_exists('ip')) {
    /**
     * @return string
     */
    function ip()
    {
        return di('request')->getClientAddress();
    }
}

if (!function_exists('curl')) {
    /**
     * @param string       $type
     * @param string|array $url
     * @param string|array $body
     * @param array        $options
     *
     * @return \ManaPHP\Curl\Easy\Response
     * @throws \ManaPHP\Curl\ConnectionException
     */
    function curl($type, $url, $body = null, $options = [])
    {
        return di('httpClient')->request($type, $url, $body, $options);
    }
}