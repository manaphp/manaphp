<?php

namespace ManaPHP\Http;

/**
 * Interface ManaPHP\Http\RequestInterface
 *
 * @package ManaPHP\Http
 */
interface RequestInterface
{
    /**
     * Gets a variable from the $_REQUEST applying filters if needed
     *
     * @param string $name
     * @param string $rule
     * @param mixed  $defaultValue
     *
     * @return mixed
     */
    public function get($name = null, $rule = null, $defaultValue = null);

    /**
     * Gets variable from $_GET applying filters if needed
     *
     * @param string $name
     * @param string $rule
     * @param mixed  $defaultValue
     *
     * @return mixed
     */
    public function getGet($name = null, $rule = null, $defaultValue = null);

    /**
     * Gets a variable from the $_POST applying filters if needed
     *
     * @param string $name
     * @param string $rule
     * @param mixed  $defaultValue
     *
     * @return mixed
     */
    public function getPost($name = null, $rule = null, $defaultValue = null);

    /**
     * Gets a variable from put request
     *
     *<code>
     *    $userEmail = $request->getPut("user_email");
     *
     *    $userEmail = $request->getPut("user_email", "email");
     *</code>
     *
     * @param string $name
     * @param string $rule
     * @param mixed  $defaultValue
     *
     * @return mixed
     */
    public function getPut($name = null, $rule = null, $defaultValue = null);

    /**
     * Gets variable from $_GET applying filters if needed
     *
     * @param string $name
     * @param string $rule
     * @param mixed  $defaultValue
     *
     * @return mixed
     */
    public function getQuery($name = null, $rule = null, $defaultValue = null);

    /**
     * Gets variable from $_SERVER
     *
     * @param string $name
     * @param mixed  $defaultValue
     *
     * @return mixed
     */
    public function getServer($name = null, $defaultValue = null);

    /**
     * Checks whether $_SERVER has certain index
     *
     * @param string $name
     *
     * @return bool
     */
    public function has($name);

    /**
     * Checks whether $_GET has certain index
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasGet($name);

    /**
     * Checks whether $_POST has certain index
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasPost($name);

    /**
     * Checks whether has certain index
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasPut($name);

    /**
     * Checks whether $_GET has certain index
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasQuery($name);

    /**
     * @return string
     */
    public function getMethod();

    /**
     * Checks whether $_GET has certain index
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasServer($name);

    /**
     * @param string $name
     *
     * @return string|null
     */
    public function getHeader($name);

    /**
     * Gets HTTP schema (http/https)
     *
     * @return string
     */
    public function getScheme();

    /**
     * Checks whether request has been made using ajax. Checks if $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest'
     *
     * @return bool
     */
    public function isAjax();

    /**
     * Gets HTTP raw request body
     *
     * @return string
     */
    public function getRawBody();

    /**
     * @param bool $assoc
     *
     * @return array|\stdClass
     */
    public function getJsonBody($assoc = true);

    /**
     * Gets most possibly client IPv4 Address. This methods search in $_SERVER['REMOTE_ADDR'] and optionally in $_SERVER['HTTP_X_FORWARDED_FOR']
     *
     * @return string
     */
    public function getClientAddress();

    /**set the client address for getClientAddress method
     *
     * @param string|callable
     */
    public function setClientAddress($address);

    /**
     * Gets HTTP user agent used to made the request
     *
     * @return string
     */
    public function getUserAgent();

    /**
     * Checks whether HTTP method is POST. if $_SERVER['REQUEST_METHOD']=='POST'
     *
     * @return bool
     */
    public function isPost();

    /**
     *
     * Checks whether HTTP method is GET. if $_SERVER['REQUEST_METHOD']=='GET'
     *
     * @return bool
     */
    public function isGet();

    /**
     * Checks whether HTTP method is PUT. if $_SERVER['REQUEST_METHOD']=='PUT'
     *
     * @return bool
     */
    public function isPut();

    /**
     * Checks whether HTTP method is HEAD. if $_SERVER['REQUEST_METHOD']=='HEAD'
     *
     * @return bool
     */
    public function isHead();

    /**
     * Checks whether HTTP method is DELETE. if $_SERVER['REQUEST_METHOD']=='DELETE'
     *
     * @return bool
     */
    public function isDelete();

    /**
     * Checks whether HTTP method is OPTIONS. if $_SERVER['REQUEST_METHOD']=='OPTIONS'
     *
     * @return bool
     */
    public function isOptions();

    /**
     * Checks whether HTTP method is PATCH. if $_SERVER['REQUEST_METHOD']=='PATCH'
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
    public function hasFiles($onlySuccessful = false);

    /**
     * Gets attached files as \ManaPHP\Http\Request\FileInterface compatible instances
     *
     * @param bool $onlySuccessful
     *
     * @return \ManaPHP\Http\Request\FileInterface[]
     */
    public function getFiles($onlySuccessful = false);

    /**
     * Gets web page that refers active request. ie: http://www.google.com
     *
     * @return string
     */
    public function getReferer();

    /**
     * http://localhost:8080/test/test.jsp
     *
     * @param bool $withQuery
     *
     * @return string
     */
    public function getUrl($withQuery = false);

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