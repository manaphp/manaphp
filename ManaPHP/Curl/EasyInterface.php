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
     * @param string|array $url
     * @param array        $headers
     * @param array        $options
     *
     * @return \ManaPHP\Curl\Easy\Response
     */
    public function get($url, $headers = [], $options = []);

    /**
     * @param string|array $url
     * @param string|array $body
     * @param array        $headers
     * @param array        $options
     *
     * @return \ManaPHP\Curl\Easy\Response
     */
    public function post($url, $body = [], $headers = [], $options = []);

    /**
     * @param string|array $url
     * @param array        $headers
     * @param array        $options
     *
     * @return \ManaPHP\Curl\Easy\Response
     */
    public function delete($url, $headers = [], $options = []);

    /**
     * @param string|array $url
     * @param string|array $body
     * @param array        $headers
     * @param array        $options
     *
     * @return \ManaPHP\Curl\Easy\Response
     */
    public function put($url, $body = [], $headers = [], $options = []);

    /**
     * @param string|array $url
     * @param string|array $body
     * @param array        $headers
     * @param array        $options
     *
     * @return \ManaPHP\Curl\Easy\Response
     */
    public function patch($url, $body = [], $headers = [], $options = []);

    /**
     * @param string|array $url
     * @param string|array $body
     * @param array        $headers
     * @param array        $options
     *
     * @return \ManaPHP\Curl\Easy\Response
     */
    public function head($url, $body = [], $headers = [], $options = []);

    /**
     * @param string|array $url
     * @param string       $file
     * @param array        $options
     *
     * @return static
     */
    public function downloadFile($url, $file, $options = []);
}