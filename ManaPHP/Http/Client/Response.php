<?php
declare(strict_types=1);

namespace ManaPHP\Http\Client;

use JsonSerializable;
use ManaPHP\Exception\InvalidJsonException;

class Response implements JsonSerializable
{
    public string $url;
    public float $process_time;
    public string $remote_ip;
    public int $http_code;
    public array $headers = [];
    public string $content_type;
    public string $body;

    public function __construct(Request $request, array $headers, string $body)
    {
        if (preg_match('#\s(?:301|302)\s#', $headers[0], $match) === 1) {
            $headers = $this->getLastHeaders($headers);
        }

        $this->url = $request->url;
        $this->remote_ip = $request->remote_ip;
        $this->process_time = $request->process_time;
        $this->headers = $headers;

        $content_type = null;
        foreach ($headers as $header) {
            if (stripos($header, 'Content-Type:') === 0) {
                $content_type = trim(substr($header, 13));
                break;
            }
        }
        $this->content_type = $content_type;

        $http_code = null;
        if ($headers && preg_match('#\d{3}#', $headers[0], $match)) {
            $http_code = (int)$match[0];
        }
        $this->http_code = $http_code;

        if ($body !== '') {
            $content_encoding = null;
            foreach ($headers as $header) {
                if (stripos($header, 'Content-Encoding:') === 0) {
                    $content_encoding = trim(substr($header, 17));
                    break;
                }
            }

            if ($content_encoding === 'gzip') {
                if (($decoded = @gzdecode($body)) === false) {
                    throw new BadResponseException(['`:url`: `:ungzip failed`', 'url' => $request->url]);
                } else {
                    $body = (string)$decoded;
                }
            } elseif ($content_encoding === 'deflate') {
                if (($decoded = @gzinflate($body)) === false) {
                    throw new BadResponseException(['`:url`: deflate failed', 'url' => $request->url]);
                } else {
                    $body = (string)$decoded;
                }
            }
        }

        $this->body = $body;
    }

    protected function getLastHeaders(array $headers): array
    {
        for ($i = count($headers) - 1; $i >= 0; $i--) {
            $header = $headers[$i];
            if (str_starts_with($header, 'HTTP/')) {
                return $i === 0 ? $headers : array_slice($headers, $i);
            }
        }

        return [];
    }

    public function getHeaders(): array
    {
        $headers = [];
        foreach ($this->headers as $header) {
            if (($pos = strpos($header, ': ')) === false) {
                continue;
            }

            $name = substr($header, 0, $pos);
            $value = substr($header, $pos + 2);
            if (isset($headers[$name])) {
                if (!is_array($headers[$name])) {
                    $headers[$name] = [$headers[$name], $value];
                } else {
                    $headers[$name][] = $value;
                }
            } else {
                $headers[$name] = $value;
            }
        }

        return $headers;
    }

    public function getJsonBody(): array
    {
        $data = json_parse($this->body);
        if (!is_array($data)) {
            $cut_body = substr($this->body, 0, 128);
            throw new InvalidJsonException(['response of `%s` is not a valid json: `%s`', $this->url, $cut_body]);
        }

        return $data;
    }

    public function getUtf8Body(): string
    {
        $body = $this->body;
        if (preg_match('#charset=([\w\-]+)#i', $this->content_type, $match) === 1) {
            $charset = strtoupper($match[1]);
            if ($charset !== 'UTF-8' && $charset !== 'UTF8') {
                $body = iconv($charset, 'UTF-8', $body);
            }
        }

        return $body;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}