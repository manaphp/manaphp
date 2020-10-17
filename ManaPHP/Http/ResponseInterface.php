<?php

namespace ManaPHP\Http;

/**
 * Interface ManaPHP\Http\ResponseInterface
 *
 * @package response
 */
interface ResponseInterface
{
    /**
     * @return \ManaPHP\Http\ResponseContext
     */
    public function getContext();

    /**
     * Sets a cookie to be sent at the end of the request
     *
     * @param string $name
     * @param mixed  $value
     * @param int    $expire
     * @param string $path
     * @param string $domain
     * @param bool   $secure
     * @param bool   $httponly
     *
     * @return static
     */
    public function setCookie($name, $value, $expire = 0, $path = null, $domain = null, $secure = false, $httponly = true);

    /**
     * Deletes a cookie by its name
     *
     * @param string $name
     * @param string $path
     * @param string $domain
     * @param bool   $secure
     * @param bool   $httponly
     *
     * @return static
     */
    public function deleteCookie($name, $path = null, $domain = null, $secure = false, $httponly = true);

    /**
     * Sets the HTTP response code
     *
     * @param int    $code
     * @param string $text
     *
     * @return static
     */
    public function setStatus($code, $text = null);

    /**
     * @return string
     */
    public function getStatus();

    /**
     * @return int
     */
    public function getStatusCode();

    /**
     * @param int $code
     *
     * @return string
     */
    public function getStatusText($code = null);

    /**
     * send a header in the response
     *
     * @param string $name
     * @param string $value
     *
     * @return static
     */
    public function setHeader($name, $value);

    /**
     * @param string $name
     * @param string $default
     *
     * @return string
     */
    public function getHeader($name, $default = null);

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasHeader($name);

    /**
     * @param string $name
     *
     * @return static
     */
    public function removeHeader($name);

    /**
     * Sets output expire time header
     *
     * @param int $timestamp
     *
     * @return static
     */
    public function setExpires($timestamp);

    /**
     * Sends a Not-Modified response
     *
     * @return static
     */
    public function setNotModified();

    /* @param string $etag
     *
     * @return static
     */
    public function setETag($etag);

    /**
     * @param string $control
     *
     * @return static
     */
    public function setCacheControl($control);

    /**
     * @param int    $age
     * @param string $extra
     *
     * @return static
     */
    public function setMaxAge($age, $extra = null);

    /**
     * Sets the response content-type mime, optionally the charset
     *
     * @param string $contentType
     * @param string $charset
     *
     * @return static
     */
    public function setContentType($contentType, $charset = null);

    /**
     * @return string
     */
    public function getContentType();

    /**
     * Redirect by HTTP to another action or URL
     *
     * @param string|array $location
     * @param bool         $temporarily
     *
     * @return static
     */
    public function redirect($location, $temporarily = true);

    /**
     * Sets HTTP response body
     *
     * @param string $content
     *
     * @return static
     */
    public function setContent($content);

    /**
     * @param string $message
     *
     * @return static
     */
    public function setJsonOk($message = '');

    /**
     * @param string $message
     * @param int    $code
     *
     * @return static
     */
    public function setJsonError($message, $code = 1);

    /**
     * @param mixed  $data
     * @param string $message
     *
     * @return static
     */
    public function setJsonData($data, $message = '');

    /**
     * Sets HTTP response body. The parameter is automatically converted to JSON
     *
     * @param array|\JsonSerializable|string|\Exception $content
     *
     * @return static
     */
    public function setJsonContent($content);

    /**
     * Gets the HTTP response body
     *
     * @return string
     */
    public function getContent();

    /**
     * Sets an attached file to be sent at the end of the request
     *
     * @param string $file
     * @param string $attachmentName
     *
     * @return static
     */
    public function setFile($file, $attachmentName = null);

    /**
     * @return string|null
     */
    public function getFile();

    /**
     * @param string $attachmentName
     *
     * @return static
     */
    public function setAttachment($attachmentName);

    /**
     * @param array        $rows
     * @param string       $name
     * @param array|string $header
     *
     * @return static
     */
    public function setCsvContent($rows, $name, $header = null);

    /**
     * @return array
     */
    public function getHeaders();
}