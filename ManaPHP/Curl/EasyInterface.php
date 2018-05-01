<?php
namespace ManaPHP\Curl;

/**
 * Interface ManaPHP\Curl\EasyInterface
 *
 * @package curl
 */
interface EasyInterface
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
     * @param string|array $url
     * @param array        $options
     *
     * @return \ManaPHP\Curl\Easy\Response
     * @throws \ManaPHP\Curl\ConnectionException
     */
    public function get($url, $options = []);

    /**
     * @param string|array $url
     * @param string|array $body
     * @param array        $options
     *
     * @return \ManaPHP\Curl\Easy\Response
     * @throws \ManaPHP\Curl\ConnectionException
     */
    public function post($url, $body = [], $options = []);

    /**
     * @param string|array $url
     * @param array        $options
     *
     * @return \ManaPHP\Curl\Easy\Response
     * @throws \ManaPHP\Curl\ConnectionException
     */
    public function delete($url, $options = []);

    /**
     * @param string|array $url
     * @param string|array $body
     * @param array        $options
     *
     * @return \ManaPHP\Curl\Easy\Response
     * @throws \ManaPHP\Curl\ConnectionException
     */
    public function put($url, $body = [], $options = []);

    /**
     * @param string|array $url
     * @param string|array $body
     * @param array        $options
     *
     * @return \ManaPHP\Curl\Easy\Response
     * @throws \ManaPHP\Curl\ConnectionException
     */
    public function patch($url, $body = [], $options = []);

    /**
     * @param string|array $url
     * @param string|array $body
     * @param array        $options
     *
     * @return \ManaPHP\Curl\Easy\Response
     * @throws \ManaPHP\Curl\ConnectionException
     */
    public function head($url, $body = [], $options = []);

    /**
     * @param array $files
     * @param array $options
     *
     * @return array
     */
    public function download($files, $options = []);
}