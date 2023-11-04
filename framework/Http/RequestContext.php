<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Coroutine\Context\Stickyable;

class RequestContext implements Stickyable
{
    public array $_GET = [];
    public array $_POST = [];
    public array $_REQUEST = [];
    public array $_SERVER = [];
    public array $_COOKIE = [];
    public array $_FILES = [];
    public ?string $rawBody;
}