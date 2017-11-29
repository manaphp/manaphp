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
     * Sets the HTTP response code
     *
     * @param int    $code
     * @param string $message
     *
     * @return static
     */
    public function setStatusCode($code, $message);

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
     * Send a raw header in the response
     *
     * @param string $header
     *
     * @return static
     */
    public function setRawHeader($header);

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
    public function setEtag($etag);

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
     * Sets HTTP response body. The parameter is automatically converted to JSON
     *<code>
     *    $response->setJsonContent(array("status" => "OK"));
     *</code>
     *
     * @param array|\Serializable $content
     * @param int                 $jsonOptions
     *
     * @return static
     */
    public function setJsonContent($content, $jsonOptions = null);

    /**
     * Appends a string to the HTTP response body
     *
     * @param string $content
     *
     * @return static
     */
    public function appendContent($content);

    /**
     * Gets the HTTP response body
     *
     * @return string
     */
    public function getContent();

    /**
     * Sends headers to the client
     *
     * @return static
     */
    public function sendHeaders();

    /**
     * Prints out HTTP response to the client
     *
     * @return static
     */
    public function send();

    /**
     * Sets an attached file to be sent at the end of the request
     *
     * @param string $file
     * @param string $attachmentName
     *
     * @return static
     */
    public function setFileToSend($file, $attachmentName = null);

    /**
     * @param string $attachmentName
     *
     * @return static
     */
    public function setAttachment($attachmentName);

    /**
     * @param array        $rows
     * @param string       $attachmentName
     * @param array|string $fields
     *
     * @return static
     */
    public function setCsvContent($rows, $attachmentName, $fields = null);

    /**
     * @return array
     */
    public function getHeaders();
}