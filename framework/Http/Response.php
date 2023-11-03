<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use DateTime;
use DateTimeZone;
use JsonSerializable;
use ManaPHP\Context\ContextTrait;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\Attribute\Config;
use ManaPHP\Di\Lazy;
use ManaPHP\Exception\AbortException;
use ManaPHP\Exception\FileNotFoundException;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Http\Response\Appenders\RequestIdAppender;
use ManaPHP\Http\Response\Appenders\ResponseTimeAppender;
use ManaPHP\Http\Response\Appenders\RouteAppender;
use Throwable;

class Response implements ResponseInterface
{
    use ContextTrait;

    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected UrlInterface|Lazy $url;
    #[Autowired] protected RouterInterface $router;

    #[Autowired] protected array $appenders
        = [
            RequestIdAppender::class,
            ResponseTimeAppender::class,
            RouteAppender::class,
        ];
    #[Autowired] protected int|string $ok_code = 0;
    #[Autowired] protected int|string $error_code = 1;

    #[Config] protected bool $app_debug;

    public function setCookie(
        string $name,
        mixed $value,
        int $expire = 0,
        ?string $path = null,
        ?string $domain = null,
        bool $secure = false,
        bool $httponly = true
    ): static {
        /** @var ResponseContext $context */
        $context = $this->getContext();

        if ($expire > 0) {
            $current = time();
            if ($expire < $current) {
                $expire += $current;
            }
        }

        $context->cookies[$name] = [
            'name'     => $name,
            'value'    => $value,
            'expire'   => $expire,
            'path'     => $path,
            'domain'   => $domain,
            'secure'   => $secure,
            'httponly' => $httponly
        ];

        return $this;
    }

    public function getCookies(): array
    {
        /** @var ResponseContext $context */
        $context = $this->getContext();

        return $context->cookies;
    }

    public function setStatus(int $code, ?string $text = null): static
    {
        /** @var ResponseContext $context */
        $context = $this->getContext();

        $context->status_code = $code;
        $context->status_text = $text ?: $this->getStatusText($code);

        return $this;
    }

    public function getStatus(): string
    {
        /** @var ResponseContext $context */
        $context = $this->getContext();

        return $context->status_code . ' ' . $context->status_text;
    }

    public function getStatusCode(): int
    {
        /** @var ResponseContext $context */
        $context = $this->getContext();

        return $context->status_code;
    }

    public function getStatusText(?int $code = null): string
    {
        if ($code === null) {
            /** @var ResponseContext $context */
            $context = $this->getContext();

            return $context->status_text;
        } else {
            $texts = [
                200 => 'OK',
                201 => 'Created',
                202 => 'Accepted',
                203 => 'Non-Authoritative Information',
                204 => 'No Content',
                205 => 'Reset Content',
                206 => 'Partial Content',
                207 => 'Multi-Status',
                208 => 'Already Reported',
                301 => 'Moved Permanently',
                302 => 'Found',
                303 => 'See Other',
                304 => 'Not Modified',
                305 => 'Use Proxy',
                307 => 'Temporary Redirect',
                308 => 'Permanent Redirect',
                400 => 'Bad Request',
                401 => 'Unauthorized',
                402 => 'Payment Required',
                403 => 'Forbidden',
                404 => 'Not Found',
                405 => 'Method Not Allowed',
                406 => 'Not Acceptable',
                407 => 'Proxy Authentication Required',
                408 => 'Request Time-out',
                409 => 'Conflict',
                410 => 'Gone',
                411 => 'Length Required',
                412 => 'Precondition Failed',
                413 => 'Request Entity Too Large',
                414 => 'Request-URI Too Long',
                415 => 'Unsupported Media Type',
                416 => 'Requested range unsatisfiable',
                417 => 'Expectation failed',
                418 => 'I\'m a teapot',
                421 => 'Misdirected Request',
                422 => 'Unprocessable entity',
                423 => 'Locked',
                424 => 'Method failure',
                425 => 'Unordered Collection',
                426 => 'Upgrade Required',
                428 => 'Precondition Required',
                429 => 'Too Many Requests',
                431 => 'Request Header Fields Too Large',
                449 => 'Retry With',
                450 => 'Blocked by Windows Parental Controls',
                500 => 'Internal Server Error',
                501 => 'Not Implemented',
                502 => 'Bad Gateway or Proxy Error',
                503 => 'Service Unavailable',
                504 => 'Gateway Time-out',
                505 => 'HTTP Version not supported',
                507 => 'Insufficient storage',
                508 => 'Loop Detected',
                509 => 'Bandwidth Limit Exceeded',
                510 => 'Not Extended',
                511 => 'Network Authentication Required',
            ];

            return $texts[$code] ?? 'App Error';
        }
    }

    public function setHeader(string $name, string $value): static
    {
        /** @var ResponseContext $context */
        $context = $this->getContext();

        $context->headers[$name] = $value;

        return $this;
    }

    public function getHeader(string $name, ?string $default = null): ?string
    {
        /** @var ResponseContext $context */
        $context = $this->getContext();

        return $context->headers[$name] ?? $default;
    }

    public function hasHeader(string $name): bool
    {
        /** @var ResponseContext $context */
        $context = $this->getContext();

        return isset($context->headers[$name]);
    }

    public function removeHeader(string $name): static
    {
        /** @var ResponseContext $context */
        $context = $this->getContext();

        unset($context->headers[$name]);

        return $this;
    }

    public function setExpires(int $timestamp): static
    {
        if (str_contains('GET,OPTIONS', $this->request->getMethod())) {
            if ($timestamp <= 2592000) {
                $timestamp += time();
            }

            $date = new DateTime('now', new DateTimeZone('UTC'));
            $date->setTimestamp($timestamp);

            $this->setHeader('Expires', $date->format('D, d M Y H:i:s') . ' GMT');
        }

        return $this;
    }

    public function setNotModified(): static
    {
        /** @var ResponseContext $context */
        $context = $this->getContext();

        $context->status_code = 304;
        $context->status_text = 'Not Modified';

        return $this;
    }

    public function setETag(string $etag): static
    {
        $this->setHeader('ETag', $etag);

        return $this;
    }

    public function setCacheControl(string $control): static
    {
        if (str_contains('GET,OPTIONS', $this->request->getMethod())) {
            return $this->setHeader('Cache-Control', $control);
        }

        return $this;
    }

    public function setMaxAge(int $age, ?string $extra = null): static
    {
        if (str_contains('GET,OPTIONS', $this->request->getMethod())) {
            $this->setHeader('Cache-Control', $extra ? "$extra, max-age=$age" : "max-age=$age");
            $this->setExpires(time() + $age);
        }

        return $this;
    }

    public function setContentType(string $contentType, ?string $charset = null): static
    {
        if ($charset === null) {
            $this->setHeader('Content-Type', $contentType);
        } else {
            $this->setHeader('Content-Type', $contentType . '; charset=' . $charset);
        }

        return $this;
    }

    public function getContentType(): ?string
    {
        /** @var ResponseContext $context */
        $context = $this->getContext();

        return $context->headers['Content-Type'] ?? null;
    }

    public function redirect(string|array $location, bool $temporarily = true): static
    {
        if ($temporarily) {
            $this->setStatus(302, 'Temporarily Moved');
        } else {
            $this->setStatus(301, 'Permanently Moved');
        }

        $this->setHeader('Location', $this->url->get($location));

        throw new AbortException();

        /** @noinspection PhpUnreachableStatementInspection */
        return $this;
    }

    public function setContent(mixed $content): static
    {
        /** @var ResponseContext $context */
        $context = $this->getContext();

        $context->content = $content;

        return $this;
    }

    public function setJsonThrowable(Throwable $throwable): static
    {
        if ($throwable instanceof \ManaPHP\Exception) {
            $status = $throwable->getStatusCode();
            $json = $throwable->getJson();
        } else {
            $status = 500;
            $json = ['code' => $status, 'msg' => 'Internal Server Error'];
        }

        if ($this->app_debug) {
            $json['msg'] = $throwable::class . ': ' . $throwable->getMessage();
            $json['exception'] = explode("\n", (string)$throwable);
        }

        return $this->json($json, $status);
    }

    public function json(mixed $content, int $status = 200): static
    {
        /** @var ResponseContext $context */
        $context = $this->getContext();

        $this->setHeader('Content-Type', 'application/json; charset=utf-8');
        $this->setStatus($status);

        if (\is_array($content) || \is_string($content)) {
            null;
        } elseif ($content instanceof JsonSerializable) {
            $content = ['code' => $this->ok_code, 'msg' => '', 'data' => $content];
        }

        $context->content = $content;

        return $this;
    }

    public function getContent(): mixed
    {
        /** @var ResponseContext $context */
        $context = $this->getContext();

        return $context->content;
    }

    public function getContentLength(): int
    {
        $content = $this->getContent();

        return \is_string($content) ? \strlen($content) : 0;
    }

    public function hasContent(): bool
    {
        /** @var ResponseContext $context */
        $context = $this->getContext();

        $content = $context->content;

        return $content !== '' && $content !== null;
    }

    public function setFile(string $file, ?string $attachmentName = null): static
    {
        /** @var ResponseContext $context */
        $context = $this->getContext();

        if ($attachmentName === null) {
            $attachmentName = basename($file);
        }

        if (!LocalFS::fileExists($file)) {
            throw new FileNotFoundException(['Sent file is not exists: `{file}`', 'file' => $file]);
        }
        $this->setHeader('Content-Length', (string)LocalFS::fileSize($file));

        $context->file = $file;

        $this->setAttachment($attachmentName);

        return $this;
    }

    public function getFile(): ?string
    {
        /** @var ResponseContext $context */
        $context = $this->getContext();

        return $context->file;
    }

    public function hasFile(): bool
    {
        /** @var ResponseContext $context */
        $context = $this->getContext();

        return (bool)$context->file;
    }

    public function setAttachment(string $attachmentName): static
    {
        if ($userAgent = $this->request->getUserAgent()) {
            if (str_contains($userAgent, 'Trident') || str_contains($userAgent, 'MSIE')) {
                $attachmentName = urlencode($attachmentName);
            }
        }

        $this->setHeader('Content-Description', 'File Transfer');
        $this->setHeader('Content-Type', 'application/octet-stream');
        $this->setHeader('Content-Disposition', 'attachment; filename=' . $attachmentName);
        $this->setHeader('Content-Transfer-Encoding', 'binary');
        $this->setHeader('Cache-Control', 'must-revalidate');

        return $this;
    }

    public function setCsvContent(array $rows, string $name, null|string|array $header = null): static
    {
        $this->setAttachment(pathinfo($name, PATHINFO_EXTENSION) === 'csv' ? $name : $name . '.csv');

        $file = fopen('php://temp', 'rb+');
        fprintf($file, "\xEF\xBB\xBF");

        if (\is_string($header)) {
            $header = explode(',', $header);
        } elseif ($header === null && $first = current($rows)) {
            $header = array_keys(\is_array($first) ? $first : $first->toArray());
        }

        if ($header !== null) {
            fputcsv($file, $header);
        }

        foreach ($rows as $row) {
            fputcsv($file, \is_array($row) ? $row : $row->toArray());
        }

        rewind($file);
        $content = stream_get_contents($file);
        fclose($file);

        $this->setContentType('text/csv');
        $this->setContent($content);

        return $this;
    }

    public function getHeaders(): array
    {
        /** @var ResponseContext $context */
        $context = $this->getContext();

        return $context->headers;
    }

    public function getAppenders(): array
    {
        return $this->appenders;
    }
}