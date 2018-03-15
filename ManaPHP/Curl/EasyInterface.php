<?php
namespace ManaPHP\Curl;

/**
 * Interface ManaPHP\Curl\EasyInterface
 *
 * @package httpClient
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
     * @return int
     */
    public function get($url, $headers = [], $options = []);

    /**
     * @param string|array $url
     * @param string|array $data
     * @param array        $headers
     * @param array        $options
     *
     * @return int
     */
    public function post($url, $data = [], $headers = [], $options = []);

    /**
     * @param string|array $url
     * @param array        $headers
     * @param array        $options
     *
     * @return int
     */
    public function delete($url, $headers = [], $options = []);

    /**
     * @param string|array $url
     * @param string|array $data
     * @param array        $headers
     * @param array        $options
     *
     * @return int
     */
    public function put($url, $data = [], $headers = [], $options = []);

    /**
     * @param string|array $url
     * @param string|array $data
     * @param array        $headers
     * @param array        $options
     *
     * @return int
     */
    public function patch($url, $data = [], $headers = [], $options = []);

    /**
     * @param string|array $url
     * @param string|array $data
     * @param array        $headers
     * @param array        $options
     *
     * @return int
     */
    public function head($url, $data = [], $headers = [], $options = []);

    /**
     * @param string|array $url
     * @param string       $file
     * @param array        $headers
     * @param array        $options
     *
     * @return string|false
     */
    public function downloadFile($url, $file, $headers = [], $options = []);

    /**
     * @return int
     */
    public function getResponseCode();

    /**
     * @param bool $assoc
     *
     * @return array
     */
    public function getResponseHeaders($assoc = true);

    /**
     * @return string
     */
    public function getResponseBody();

}