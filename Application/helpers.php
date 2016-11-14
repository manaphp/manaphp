<?php
use ManaPHP\Di;

if (!function_exists('abort')) {
    /**
     * Throw an ManaPHP\Application\AbortException with the given data.
     *
     * @param  int    $code
     * @param  string $message
     *
     * @return void
     *
     * @throws \ManaPHP\Application\AbortException
     */
    function abort($code, $message = '')
    {
        Di::getDefault()->application->abort($code, $message);
    }
}

if (!function_exists('abort_if')) {
    /**
     * Throw an ManaPHP\Application\AbortException with the given data if the given condition is true.
     *
     * @param  bool   $boolean
     * @param  int    $code
     * @param  string $message
     *
     * @return void
     *
     * @throws \ManaPHP\Application\AbortException
     */
    function abort_if($boolean, $code, $message = '')
    {
        if ($boolean) {
            abort($code, $message);
        }
    }
}

if (!function_exists('app')) {
    /**
     * Get the Application instance.
     *
     * @return \ManaPHP\ApplicationInterface
     */
    function app()
    {
        return Di::getDefault()->application;
    }
}

if (!function_exists('cache')) {
    /**
     * Get / set the specified cache value.
     *
     * If an array is passed, we'll assume you want to put to the cache.
     *
     * @param  dynamic  key|key,default|key,data,expiration|null
     *
     * @return \ManaPHP\CacheInterface|string|false
     *
     * @throws \Exception
     */
    function cache()
    {
        $arguments = func_get_args();

        switch (count($arguments)) {
            case 0:
                return Di::getDefault()->cache;
                break;
            case 1:
                return Di::getDefault()->cache->get($arguments[0]);
            case 2:
                return Di::getDefault()->cache->get($arguments) ?: $arguments[1];
            case 3:
                Di::getDefault()->cache->set($arguments[0], $arguments[1], $arguments[2]);
                return null;
            default:
                throw new \ManaPHP\Cache\Exception('too many arguments');
        }
    }
}

if (!function_exists('request')) {
    /**
     * Get an instance of the current request or an input item from the request.
     *
     * @param  string $key
     * @param  string $rule
     * @param  mixed  $default
     *
     * @return \ManaPHP\Http\Request|string|array
     */
    function request($key = null, $rule = null, $default = null)
    {
        if ($key === null) {
            return Di::getDefault()->request;
        } else {
            return Di::getDefault()->request->get($key, $rule, $default);
        }
    }
}

if (!function_exists('response')) {
    /**
     * Return a new response from the application.
     *
     * @param  null|string|array $content
     *
     * @return \ManaPHP\Http\ResponseInterface
     */
    function response($content = null)
    {
        if ($content === null) {
            return Di::getDefault()->response;
        } elseif (is_array($content)) {
            return Di::getDefault()->response->setJsonContent($content);
        } else {
            return Di::getDefault()->response->setContent($content);
        }
    }
}

if (!function_exists('translate')) {
    /**
     * Translate the given message.
     *
     * @param  string $id
     * @param  array  $parameters
     *
     * @return \ManaPHP\I18n\TranslationInterface |string
     */
    function translate($id = null, $parameters = [])
    {
        if (!$id) {
            return Di::getDefault()->translation;
        } else {
            return Di::getDefault()->translation->translate($id, $parameters);
        }
    }
}

if (!function_exists('url')) {
    /**
     * Generate a url for the application.
     *
     * @param  string $path
     * @param  array  $parameters
     * @param  string $module
     *
     * @return \ManaPHP\Mvc\UrlInterface|string
     */
    function url($path = null, $parameters = [], $module = null)
    {
        if ($path === null) {
            return Di::getDefault()->url;
        } else {
            return Di::getDefault()->url->get($path, $parameters, $module);
        }
    }
}

if (!function_exists('info')) {
    /**
     * Write some information to the log.
     *
     * @param  string $message
     * @param  array  $context
     *
     * @return void
     */
    function info($message, $context = [])
    {
        Di::getDefault()->logger->info($message, $context);
    }
}

if (!function_exists('session')) {
    /**
     * Get / set the specified session value.
     *
     * If an array is passed as the key, we will assume you want to set an array of values.
     *
     * @param  array|string $key
     * @param  mixed        $default
     *
     * @return mixed
     */
    function session($key = null, $default = null)
    {
        if ($key === null) {
            return Di::getDefault()->session;
        }

        if (is_array($key)) {
            $session = Di::getDefault()->session;
            foreach ($key as $k => $v) {
                $session->set($k, $v);
            }
        } else {
            return Di::getDefault()->session->get($key, $default);
        }
    }
}
