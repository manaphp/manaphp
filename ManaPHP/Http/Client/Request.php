<?php

namespace ManaPHP\Http\Client;

use JsonSerializable;

class Request implements JsonSerializable
{
    /**
     * @var string
     */
    public $method;

    /**
     * @var string
     */
    public $url;

    /**
     * @var array
     */
    public $headers;

    /**
     * @var string|array
     */
    public $body;

    /**
     * @var array
     */
    public $options;

    /**
     * @var float
     */
    public $process_time;

    /**
     * @var string
     */
    public $remote_ip;

    /**
     * @param string       $method
     * @param string|array $url
     * @param string|array $body
     * @param array        $headers
     * @param array        $options
     */
    public function __construct($method, $url, $body, $headers, $options)
    {
        $this->method = $method;

        if (is_array($url)) {
            if (count($url) > 1) {
                $uri = $url[0];
                unset($url[0]);
                $url = $uri . (str_contains($uri, '?') ? '&' : '?') . http_build_query($url);
            } else {
                $url = $url[0];
            }
        }
        $this->url = $url;

        $this->body = $body;
        $this->headers = $headers;
        $this->options = $options;
    }

    /**
     * @param string $boundary
     *
     * @return string
     */
    public function buildMultipart($boundary)
    {
        $data = '';
        foreach ($this->body as $k => $v) {
            $part = "--$boundary\r\n";

            if ($v instanceof FileInterface) {
                $postName = $v->getPostName();
                $mimeType = $v->getMimeType();
                $part .= "Content-Disposition: form-data; name=\"$k\"; filename=\"$postName\"\r\n";
                $part .= "Content-Type: $mimeType\r\n\r\n";
                $part .= $v->getContent();
            } else {
                $part .= "Content-Disposition: form-data; name=\"$k\"\r\n\r\n";
                $part .= $v;
            }

            $data .= "$part\r\n";
        }

        return $data . "--$boundary--\r\n";
    }

    /**
     * @return bool
     */
    public function hasFile()
    {
        if (!is_array($this->body) || isset($this->headers['Content-Type'])) {
            return false;
        }

        foreach ($this->body as $v) {
            if ($v instanceof FileInterface) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}