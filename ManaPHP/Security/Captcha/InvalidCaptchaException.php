<?php
namespace ManaPHP\Security\Captcha;

use ManaPHP\Exception;

class InvalidCaptchaException extends Exception
{
    public function getStatusCode()
    {
        return 200;
    }

    public function getStatusText()
    {
        return '验证码错误';
    }
}