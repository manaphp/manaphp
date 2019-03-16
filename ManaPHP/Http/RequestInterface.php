<?php

namespace ManaPHP\Http;

/**
 * Interface ManaPHP\Http\RequestInterface
 *
 * @package request
 */
interface RequestInterface
{
    /**
     * @return \ManaPHP\Http\RequestContext
     */
    public function getGlobals();

    /**
     * @param string $name
     * @param mixed  $rule
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($name = null, $rule = null, $default = '');

    /**
     * @param string $name
     * @param mixed  $rule
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getGet($name = null, $rule = null, $default = '');

    /**
     * @param string $name
     * @param mixed  $rule
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getPost($name = null, $rule = null, $default = '');

    /**
     * @param string $name
     * @param mixed  $rule
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getPut($name = null, $rule = null, $default = '');

    /**
     * @param string $name
     * @param mixed  $rule
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getQuery($name = null, $rule = null, $default = '');

    /**
     * @param string $name
     * @param mixed  $rule
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getInput($name = null, $rule = null, $default = '');

    /**
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getServer($name = null, $default = '');

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has($name);

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasGet($name);

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasPost($name);

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasPut($name);

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasQuery($name);

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasInput($name);

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasServer($name);

    /**
     * Gets HTTP schema (http/https)
     *
     * @return string
     */
    public function getScheme();

    /**
     * @return bool
     */
    public function isAjax();

    /**
     * @return string
     */
    public function getClientIp();

    /**
     * @return string
     */
    public function getUserAgent();

    /**
     * Checks whether HTTP method is POST.
     *
     * @return bool
     */
    public function isPost();

    /**
     *
     * Checks whether HTTP method is GET.
     *
     * @return bool
     */
    public function isGet();

    /**
     * Checks whether HTTP method is PUT.
     *
     * @return bool
     */
    public function isPut();

    /**
     * Checks whether HTTP method is HEAD.
     *
     * @return bool
     */
    public function isHead();

    /**
     * Checks whether HTTP method is DELETE.
     *
     * @return bool
     */
    public function isDelete();

    /**
     * Checks whether HTTP method is OPTIONS.
     *
     * @return bool
     */
    public function isOptions();

    /**
     * Checks whether HTTP method is PATCH.
     *
     * @return bool
     */
    public function isPatch();

    /**
     * Checks whether request include attached files
     *
     * @param bool $onlySuccessful
     *
     * @return bool
     */
    public function hasFiles($onlySuccessful = true);

    /**
     * Gets attached files as \ManaPHP\Http\Request\FileInterface compatible instances
     *
     * @param bool $onlySuccessful
     *
     * @return \ManaPHP\Http\Request\FileInterface[]
     */
    public function getFiles($onlySuccessful = true);

    /**
     * Gets web page that refers active request. ie: http://www.google.com
     *
     * @return string
     */
    public function getReferer();

    /**
     * http://localhost:8080/test/test.jsp
     *
     *
     * @return string
     */
    public function getUrl();

    /**
     *  /test/test.jsp
     *
     *
     * @return string
     */
    public function getUri();

    /**
     * @return string|null
     */
    public function getAccessToken();
}