<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use JsonSerializable;
use ManaPHP\Context\ContextTrait;
use ManaPHP\Di\Attribute\Value;
use ManaPHP\Http\Globals\Proxy;

class Globals implements GlobalsInterface, JsonSerializable
{
    use ContextTrait;

    #[Value] protected bool $proxy = false;

    public function __construct()
    {
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
        /** @var GlobalsContext $context */
        $context = $this->getContext();

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
        /** @var GlobalsContext $context */
        $context = $this->getContext();

        return $context;
    }

    public function getServer(): array
    {
        /** @var GlobalsContext $context */
        $context = $this->getContext();

        return $context->_SERVER;
    }

    public function setServer(string $name, mixed $value): static
    {
        /** @var GlobalsContext $context */
        $context = $this->getContext();

        $context->_SERVER[$name] = $value;

        return $this;
    }

    public function getFiles(): array
    {
        /** @var GlobalsContext $context */
        $context = $this->getContext();

        return $context->_FILES;
    }

    public function getRequest(): array
    {
        /** @var GlobalsContext $context */
        $context = $this->getContext();

        return $context->_REQUEST;
    }

    public function getRawBody(): ?string
    {
        /** @var GlobalsContext $context */
        $context = $this->getContext();

        return $context->rawBody;
    }

    public function getCookie(): array
    {
        /** @var GlobalsContext $context */
        $context = $this->getContext();

        return $context->_COOKIE;
    }

    public function setCookie(string $name, string $value): static
    {
        /** @var GlobalsContext $context */
        $context = $this->getContext();

        $context->_COOKIE[$name] = $value;

        return $this;
    }

    public function unsetCookie(string $name): static
    {
        /** @var GlobalsContext $context */
        $context = $this->getContext();

        unset($context->_COOKIE[$name]);

        return $this;
    }

    public function jsonSerialize(): array
    {
        return (array)$this->get();
    }
}