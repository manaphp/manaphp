<?php
declare(strict_types=1);

namespace ManaPHP\Http\Captcha;

use ManaPHP\Exception;

class InvalidCaptchaException extends Exception
{
    public function getStatusCode(): int
    {
        return 200;
    }

    public function getJson(): array
    {
        return ['code' => 1, 'message' => '验证码错误', 'field' => 'captcha'];
    }
}