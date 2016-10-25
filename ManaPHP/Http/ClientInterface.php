<?php
namespace ManaPHP\Http;

/**
 * Interface ManaPHP\Http\ClientInterface
 *
 * @package httpClient
 */
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
     * @return string
     */
    public function getResponseBody();
}