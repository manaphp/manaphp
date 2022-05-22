<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Component;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Http\Request\File\Exception as FileException;
use ManaPHP\Http\Request\FileInterface;

/**
 * @property-read \ManaPHP\Di\FactoryInterface           $factory
 * @property-read \ManaPHP\Http\GlobalsInterface         $globals
 * @property-read \ManaPHP\Validating\ValidatorInterface $validator
 */
class Request extends Component implements RequestInterface
{
    public function getRawBody(): string
    {
        return $this->globals->getRawBody();
    }

    protected function normalizeValue(string $field, mixed $value, mixed $default): mixed
    {
        $type = gettype($default);

        if ($type === 'string') {
            return (string)$value;
        } elseif ($type === 'integer') {
            return $this->validator->validateValue($field, $value, 'int');
        } elseif ($type === 'double') {
            return $this->validator->validateValue($field, $value, 'float');
        } elseif ($type === 'boolean') {
            return (bool)$this->validator->validateValue($field, $value, 'bool');
        } else {
            return $value;
        }
    }

    public function get(?string $name = null, mixed $default = null): mixed
    {
        $source = $this->globals->getRequest();

        if ($name === null) {
            return $source;
        }

        if (isset($source[$name]) && $source[$name] !== '') {
            $value = $source[$name];

            if (is_array($value) && is_scalar($default)) {
                throw new InvalidValueException(['the value of `:name` name is not scalar', 'name' => $name]);
            }

            return $default === null ? $value : $this->normalizeValue($name, $value, $default);
        } elseif ($default === null) {
            return $this->validator->validateValue($name, null, ['required']);
        } else {
            return $default;
        }
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

                        $r[] = $this->factory->make('ManaPHP\Http\Request\File', $file);
                    }
                }
            } elseif (is_int($files['error'])) {
                $file = $files;
                if (!$onlySuccessful || $file['error'] === UPLOAD_ERR_OK) {
                    $file['key'] = $key;

                    $r[] = $this->factory->make('ManaPHP\Http\Request\File', $file);
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
                        $r[] = $this->factory->make('ManaPHP\Http\Request\File', $file);
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
            throw new FileException(['can not found uploaded `:key` file', 'key' => $key]);
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