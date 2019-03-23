<?php
namespace ManaPHP\Http;

interface ClientInterface
{
    /**
     * @param string $proxy
     * @param bool   $peek
     *
     * @return static
     */
    public function setProxy($proxy = '127.0.0.1:8888', $peek = true);

    /**
     * @param string $file
     *
     * @return static
     */
    public function setCaFile($file);

    /**
     * @param int $seconds
     *
     * @return static
     */
    public function setTimeout($seconds);

    /**
     * @param bool $verify
     *
     * @return static
     */
    public function setSslVerify($verify);

    /**
     * @param string           $type
     * @param string|array     $url
     * @param string|array     $body
     * @param array|string|int $options
     *
     * @return array
     * @throws \ManaPHP\Http\Client\ServiceUnavailableException
     * @throws \ManaPHP\Http\Client\BadRequestException
     * @throws \ManaPHP\Http\Client\ContentTypeException
     * @throws \ManaPHP\Http\Client\JsonDecodeException
     * @throws \ManaPHP\Http\Client\ConnectionException
     */
    public function rest($type, $url, $body = null, $options = []);

    /**
     * @param string           $type
     * @param string|array     $url
     * @param string|array     $body
     * @param array|string|int $options
     *
     * @return \ManaPHP\Http\Client\Response
     * @throws \ManaPHP\Http\Client\ConnectionException
     */
    public function request($type, $url, $body = null, $options = []);

    /**
     * @param string|array     $url
     * @param array|string|int $options
     *
     * @return \ManaPHP\Http\Client\Response
     * @throws \ManaPHP\Http\Client\ConnectionException
     */
    public function get($url, $options = []);

    /**
     * @param string|array     $url
     * @param string|array     $body
     * @param array|string|int $options
     *
     * @return \ManaPHP\Http\Client\Response
     * @throws \ManaPHP\Http\Client\ConnectionException
     */
    public function post($url, $body = [], $options = []);

    /**
     * @param string|array     $url
     * @param array|string|int $options
     *
     * @return \ManaPHP\Http\Client\Response
     * @throws \ManaPHP\Http\Client\ConnectionException
     */
    public function delete($url, $options = []);

    /**
     * @param string|array     $url
     * @param string|array     $body
     * @param array|string|int $options
     *
     * @return \ManaPHP\Http\Client\Response
     * @throws \ManaPHP\Http\Client\ConnectionException
     */
    public function put($url, $body = [], $options = []);

    /**
     * @param string|array     $url
     * @param string|array     $body
     * @param array|string|int $options
     *
     * @return \ManaPHP\Http\Client\Response
     * @throws \ManaPHP\Http\Client\ConnectionException
     */
    public function patch($url, $body = [], $options = []);

    /**
     * @param string|array     $url
     * @param string|array     $body
     * @param array|string|int $options
     *
     * @return \ManaPHP\Http\Client\Response
     * @throws \ManaPHP\Http\Client\ConnectionException
     */
    public function head($url, $body = [], $options = []);

    /**
     * @param string|array           $files
     * @param string|int|float|array $options
     *
     * @return string|array
     */
    public function download($files, $options = []);

    /**
     * @return \ManaPHP\Http\Client\Response
     */
    public function getLastResponse();
}