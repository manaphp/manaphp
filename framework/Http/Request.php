<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use JsonSerializable;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\MakerInterface;
use ManaPHP\Http\Request\File;
use ManaPHP\Http\Request\File\Exception as FileException;
use ManaPHP\Http\Request\FileInterface;

class Request implements RequestInterface, JsonSerializable
{
    #[Autowired] protected MakerInterface $maker;
    #[Autowired] protected GlobalsInterface $globals;

    public function getRawBody(): string
    {
        return $this->globals->getRawBody();
    }

    public function all(): array
    {
        return $this->globals->getRequest();
    }

    public function only(array $names): array
    {
        $data = [];
        $request = $this->globals->getRequest();

        foreach ($names as $name) {
            if (($val = $request[$name] ?? null) !== null) {
                $data[$name] = $val;
            }
        }

        return $data;
    }

    public function except(array $names): array
    {
        $data = [];

        foreach ($this->globals->getRequest() as $name => $val) {
            if (!in_array($name, $names, true)) {
                $data[$name] = $val;
            }
        }

        return $data;
    }

    public function get(string $name, mixed $default = null): mixed
    {
        return $this->globals->getRequest()[$name] ?? $default;
    }

    public function set(string $name, mixed $value): static
    {
        $globals = $this->globals->get();

        $globals->_GET[$name] = $value;
        $globals->_REQUEST[$name] = $value;

        return $this;
    }

    public function delete(string $name): static
    {
        $globals = $this->globals->get();

        unset($globals->_GET[$name], $globals->_POST[$name], $globals->_REQUEST[$name]);

        return $this;
    }

    public function getServer(?string $name = null, mixed $default = ''): mixed
    {
        return $this->globals->getServer()[$name] ?? $default;
    }

    public function getMethod(): string
    {
        return $this->getServer('REQUEST_METHOD');
    }

    public function has(string $name): bool
    {
        return isset($this->globals->getRequest()[$name]);
    }

    public function hasServer(string $name): bool
    {
        return isset($this->globals->getServer()[$name]);
    }

    public function getScheme(): string
    {
        if ($scheme = $this->getServer('REQUEST_SCHEME')) {
            return $scheme;
        } else {
            return $this->getServer('HTTPS') === 'on' ? 'https' : 'http';
        }
    }

    public function isAjax(): bool
    {
        return $this->getServer('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest';
    }

    public function isWebSocket(): bool
    {
        return $this->getServer('HTTP_UPGRADE') === 'websocket';
    }

    public function getClientIp(): string
    {
        return $this->getServer('HTTP_X_REAL_IP') ?: $this->getServer('REMOTE_ADDR');
    }

    public function getUserAgent(int $max_len = -1): string
    {
        $user_agent = $this->getServer('HTTP_USER_AGENT');

        return $max_len > 0 && strlen($user_agent) > $max_len ? substr($user_agent, 0, $max_len) : $user_agent;
    }

    public function isPost(): bool
    {
        return $this->getMethod() === 'POST';
    }

    public function isGet(): bool
    {
        return $this->getMethod() === 'GET';
    }

    public function isPut(): bool
    {
        return $this->getMethod() === 'PUT';
    }

    public function isPatch(): bool
    {
        return $this->getMethod() === 'PATCH';
    }

    public function isHead(): bool
    {
        return $this->getMethod() === 'HEAD';
    }

    public function isDelete(): bool
    {
        return $this->getMethod() === 'DELETE';
    }

    public function isOptions(): bool
    {
        return $this->getMethod() === 'OPTIONS';
    }

    public function hasFiles(bool $onlySuccessful = true): bool
    {
        foreach ($this->globals->getFiles() as $file) {
            if (is_int($file['error'])) {
                $error = $file['error'];

                if (!$onlySuccessful || $error === UPLOAD_ERR_OK) {
                    return true;
                }
            } else {
                foreach ($file['error'] as $error) {
                    if (!$onlySuccessful || $error === UPLOAD_ERR_OK) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function getFiles(bool $onlySuccessful = true): array
    {
        $r = [];

        foreach ($this->globals->getFiles() as $key => $files) {
            if (isset($files[0])) {
                foreach ($files as $file) {
                    if (!$onlySuccessful || $file['error'] === UPLOAD_ERR_OK) {
                        $file['key'] = $key;

                        $r[] = $this->maker->make(File::class, [$file]);
                    }
                }
            } elseif (is_int($files['error'])) {
                $file = $files;
                if (!$onlySuccessful || $file['error'] === UPLOAD_ERR_OK) {
                    $file['key'] = $key;

                    $r[] = $this->maker->make(File::class, [$file]);
                }
            } else {
                $countFiles = count($files['error']);
                for ($i = 0; $i < $countFiles; $i++) {
                    if (!$onlySuccessful || $files['error'][$i] === UPLOAD_ERR_OK) {
                        $file = [
                            'key'      => $key,
                            'name'     => $files['name'][$i],
                            'type'     => $files['type'][$i],
                            'tmp_name' => $files['tmp_name'][$i],
                            'error'    => $files['error'][$i],
                            'size'     => $files['size'][$i],
                        ];
                        $r[] = $this->maker->make(File::class, [$file]);
                    }
                }
            }
        }

        return $r;
    }

    public function getFile(?string $key = null): FileInterface
    {
        $files = $this->getFiles();

        if ($key === null) {
            if ($files) {
                return current($files);
            } else {
                throw new FileException('can not found any uploaded files');
            }
        } else {
            foreach ($files as $file) {
                if ($file->getKey() === $key) {
                    return $file;
                }
            }
            throw new FileException(['can not found uploaded `{key}` file', 'key' => $key]);
        }
    }

    public function hasFile(?string $key = null): bool
    {
        $files = $this->getFiles();

        if ($key === null) {
            return count($files) > 0;
        } else {
            foreach ($files as $file) {
                if ($file->getKey() === $key) {
                    return true;
                }
            }
            return false;
        }
    }

    public function getReferer(int $max_len = -1): string
    {
        $referer = $this->getServer('HTTP_REFERER');

        return $max_len > 0 && strlen($referer) > $max_len ? substr($referer, 0, $max_len) : $referer;
    }

    public function getOrigin(bool $strict = true): string
    {
        if ($origin = $this->getServer('HTTP_ORIGIN')) {
            return $origin;
        }

        if (!$strict && ($referer = $this->getServer('HTTP_REFERER'))) {
            if ($pos = strpos($referer, '?')) {
                $referer = substr($referer, 0, $pos);
            }

            if ($pos = strpos($referer, '://')) {
                $pos = strpos($referer, '/', $pos + 3);
                return $pos ? substr($referer, 0, $pos) : $referer;
            }
        }

        return '';
    }

    public function getHost(): string
    {
        return $this->getServer('HTTP_HOST');
    }

    public function getUrl(): string
    {
        return strip_tags(
            $this->getScheme() . '://' . $this->getServer('HTTP_HOST') . $this->getServer(
                'REQUEST_URI'
            )
        );
    }

    public function getUri(): string
    {
        return strip_tags($this->getServer('REQUEST_URI'));
    }

    public function getQueryString(): string
    {
        return $this->getServer('QUERY_STRING');
    }

    public function getToken(string $name = 'token'): string
    {
        if ($token = $this->get($name, '')) {
            return $token;
        } elseif ($token = $this->getServer('HTTP_AUTHORIZATION')) {
            $parts = explode(' ', $token, 2);
            if ($parts[0] === 'Bearer' && count($parts) === 2) {
                return $parts[1];
            }
        }

        return '';
    }

    public function jsonSerialize(): array
    {
        return (array)$this->globals->get();
    }

    public function getRequestId(): string
    {
        return $this->getServer('HTTP_X_REQUEST_ID') ?: $this->setRequestId();
    }

    public function setRequestId(?string $request_id = null): string
    {
        if ($request_id !== null) {
            $request_id = preg_replace('#[^\-\w.]#', 'X', $request_id);
        }

        if (!$request_id) {
            $request_id = bin2hex(random_bytes(16));
        }

        $this->globals->setServer('HTTP_X_REQUEST_ID', $request_id);

        return $request_id;
    }

    public function getRequestTime(): float
    {
        return $this->getServer('REQUEST_TIME_FLOAT');
    }

    public function getElapsedTime(int $precision = 3): float
    {
        return round(microtime(true) - $this->getRequestTime(), $precision);
    }

    public function getIfNoneMatch(): string
    {
        return $this->getServer('HTTP_IF_NONE_MATCH');
    }

    public function getAcceptLanguage(): string
    {
        return $this->getServer('HTTP_ACCEPT_LANGUAGE');
    }
}