<?php

namespace ManaPHP\Http\Captcha;

use ManaPHP\Exception;

class InvalidCaptchaException extends Exception
{
    /**
     * @return int
     */
    public function getStatusCode()
    {
        return 200;
    }

    /**
     * @return array
     */
    public function getJson()
    {
        return ['code' => 1, 'message' => '验证码错误', 'field' => 'captcha'];
    }
}