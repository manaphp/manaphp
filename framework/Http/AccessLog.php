<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use DateTimeInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Helper\LocalFS;
use Psr\Log\LoggerInterface;
use Throwable;
use function substr;

class AccessLog implements AccessLogInterface
{
    #[Autowired] protected LoggerInterface $logger;
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected ResponseInterface $response;
    #[Autowired] protected DispatcherInterface $dispatcher;
    #[Autowired] protected CookiesInterface $cookies;

    #[Autowired] protected bool $enabled = true;
    #[Autowired] protected string $default_value = '';
    #[Autowired] protected string $file = '@runtime/accessLog/access.log';
    #[Autowired] protected string $format = '
time=$time_iso8601
 client_ip=$client_ip
 status=$status
 request_time=$request_time
 request_method=$request_method
 request_url="$request_uri$is_args$query_string"
 request_path=$request_path
 body_bytes_sent=$body_bytes_sent
 http_referer="$http_referer"
 http_user_agent="$http_user_agent"
 http_x_forwarded_for="$http_x_forwarded_for"
 remote_addr=$remote_addr
 ';

    public function __construct()
    {
        $this->format = str_replace(["\r", "\n"], '', $this->format);
    }

    protected function getVar(string $name): string
    {
        if (str_starts_with($name, 'request_')) {
            if ($name === 'request_method') {
                return $this->request->method();
            } elseif ($name === 'request_uri') {
                return $this->request->path();
            } elseif ($name === 'request_url') {
                return $this->request->url();
            } elseif ($name === 'request_time') {
                return sprintf('%.3f', $this->request->elapsed());
            } elseif ($name === 'request_handler') {
                return (string)$this->dispatcher->getHandler();
            } else {
                return $this->default_value;
            }
        } elseif (str_starts_with($name, 'http_')) {
            return (string)$this->request->header(substr($name, 5), $this->default_value);
        } elseif (str_starts_with($name, 'cookie_')) {
            return $this->cookies->get(substr($name, 7), $this->default_value);
        } elseif (str_starts_with($name, 'arg_')) {
            return $this->request->input(substr($name, 4), $this->default_value);
        } elseif ($name === 'client_ip') {
            return $this->request->ip();
        } elseif ($name === 'time_iso8601' || $name === 'time') {
            return date(DateTimeInterface::ATOM);
        } elseif ($name === 'status') {
            return (string)$this->response->getStatusCode();
        } elseif ($name === 'body_bytes_sent') {
            return (string)$this->response->getContentLength();
        } elseif ($name === 'is_args') {
            return $this->request->server('QUERY_STRING', '') === '' ? '' : '?';
        } elseif ($name === 'query_string') {
            return $this->request->server('QUERY_STRING', '');
        } elseif (($server = $this->request->server(strtoupper($name))) !== null) {
            return (string)$server;
        } else {
            return $this->default_value;
        }
    }

    public function log(): void
    {
        if ($this->enabled) {
            try {
                $content = preg_replace_callback('#\$(\w+)#', function ($matches) {
                    return $this->getVar($matches[1]);
                }, $this->format);

                LocalFS::fileAppend($this->file, $content . PHP_EOL);
            } catch (Throwable $throwable) {
                $this->logger->error('write access log failed', ['exception' => $throwable]);
            }
        }
    }
}