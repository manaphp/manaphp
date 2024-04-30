<?php
declare(strict_types=1);

namespace ManaPHP\Http\Client;

use JsonSerializable;
use function count;
use function is_array;

class Request implements JsonSerializable
{
    public string $method;
    public string $url;
    public array $headers;
    public null|string|array $body;
    public array $options;
    public float $process_time;
    public string $remote_ip;

    public function __construct(string $method, string|array $url, null|string|array $body, array $headers,
        array $options
    ) {
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

    public function buildMultipart(string $boundary): string
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

    public function hasFile(): bool
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

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}