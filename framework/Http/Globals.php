<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Component;
use ManaPHP\Http\Globals\Proxy;

/**
 * @property-read \ManaPHP\Http\GlobalsContext $context
 */
class Globals extends Component implements GlobalsInterface
{
    protected bool $proxy;

    public function __construct(bool $proxy = false)
    {
        $this->proxy = $proxy;

        if ($this->proxy) {
            $_GET = new Proxy($this, '_GET');
            $_POST = new Proxy($this, '_POST');
            $_REQUEST = new Proxy($this, '_REQUEST');
            $_FILES = new Proxy($this, '_FILES');
            $_COOKIE = new Proxy($this, '_COOKIE');
            $_SERVER = new Proxy($this, '_SERVER');
        }
    }

    public function prepare(array $GET, array $POST, array $SERVER, ?string $RAW_BODY = null, array $COOKIE = [],
        array $FILES = []
    ): void {
        $context = $this->context;

        if (!$POST
            && (isset($SERVER['REQUEST_METHOD']) && !in_array($SERVER['REQUEST_METHOD'], ['GET', 'OPTIONS'], true))
        ) {
            if (isset($SERVER['CONTENT_TYPE'])
                && str_contains($SERVER['CONTENT_TYPE'], 'application/json')
            ) {
                $POST = json_parse($RAW_BODY);
            } else {
                parse_str($RAW_BODY, $POST);
            }

            if (!is_array($POST)) {
                $POST = [];
            }
        }

        $context->_GET = $GET;
        $context->_POST = $POST;
        $context->_REQUEST = $POST === [] ? $GET : array_merge($GET, $POST);
        $context->_SERVER = $SERVER;
        $context->rawBody = $RAW_BODY;
        $context->_COOKIE = $COOKIE;
        $context->_FILES = $FILES;
    }

    public function get(): GlobalsContext
    {
        return $this->context;
    }

    public function getServer(): array
    {
        return $this->context->_SERVER;
    }

    public function setServer(string $name, mixed $value): static
    {
        $this->context->_SERVER[$name] = $value;

        return $this;
    }

    public function getFiles(): array
    {
        return $this->context->_FILES;
    }

    public function getRequest(): array
    {
        return $this->context->_REQUEST;
    }

    public function getRawBody(): ?string
    {
        return $this->context->rawBody;
    }

    public function getCookie(): array
    {
        return $this->context->_COOKIE;
    }

    public function setCookie(string $name, string $value): static
    {
        $this->context->_COOKIE[$name] = $value;

        return $this;
    }

    public function unsetCookie(string $name): static
    {
        unset($this->context->_COOKIE[$name]);

        return $this;
    }

    public function dump(): array
    {
        $data = parent::dump();

        if (DIRECTORY_SEPARATOR === '\\') {
            foreach (['PATH', 'SystemRoot', 'COMSPEC', 'PATHEXT', 'WINDIR'] as $name) {
                unset($data['context']['_SERVER'][$name]);
            }
        }

        return $data;
    }
}