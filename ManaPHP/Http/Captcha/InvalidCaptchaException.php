<?php

namespace ManaPHP\Http\Captcha;

use ManaPHP\Exception;

class InvalidCaptchaException extends Exception
{
    public function getStatusCode()
    {
        return 200;
    }

    public function getJson()
    {
        return ['code' => 1, 'message' => '验证码错误', 'field' => 'captcha'];
    }
}