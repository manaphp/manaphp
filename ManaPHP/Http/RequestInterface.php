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
     * @return string
     */
    public function getRawBody();

    /**
     * Gets a cookie
     *
     * @param string $name
     * @param string $default
     *
     * @return mixed|null
     */
    public function getCookie($name = null, $default = '');

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasCookie($name);

    /**
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($name = null, $default = null);

    /**
     * @param string $name
     *
     * @return int|string
     */
    public function getId($name = 'id');

    /**
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getServer($name = null, $default = '');

    /**
     * @return string
     */
    public function getMethod();

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
     * @return bool
     */
    public function isWebSocket();

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
     * @param string $key
     *
     * @return \ManaPHP\Http\Request\FileInterface
     */
    public function getFile($key = null);

    /**
     * Gets web page that refers active request. ie: http://www.google.com
     *
     * @return string
     */
    public function getReferer();

    /**
     * @param bool $strict
     *
     * @return string
     */
    public function getOrigin($strict = true);

    /**
     * @return string
     */
    public function getHost();

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
     * @param string $name
     *
     * @return string|null
     */
    public function getToken($name = 'token');

    /**
     * @return string
     */
    public function getRequestId();

    /**
     * @param string $request_id
     *
     * @return void
     */
    public function setRequestId($request_id = null);
}