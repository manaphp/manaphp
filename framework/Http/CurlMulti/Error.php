<?php
declare(strict_types=1);

namespace ManaPHP\Http\CurlMulti;

class Error
{
    public int $code;
    public string $message;
    public Request $request;
}