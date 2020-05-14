<?php

namespace ManaPHP\Socket;

interface ResponseInterface
{
    /**
     * @return \ManaPHP\Socket\ResponseContext
     */
    public function getContext();

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
     * @param array|\JsonSerializable|int|string|\Exception $content
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
}