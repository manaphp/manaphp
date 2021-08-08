<?php

namespace App\Areas\User\Controllers;

use App\Controllers\Controller;
use App\Models\User;

/**
 * @property-read \ManaPHP\Http\CaptchaInterface $captcha
 */
class AccountController extends Controller
{
    public function getAcl()
    {
        return ['*' => '*'];
    }

    public function captchaAction()
    {
        return $this->captcha->generate();
    }

    public function registerAction()
    {
        $this->captcha->verify();
        return User::rCreate(['user_name', 'email', 'password', 'status' => User::STATUS_ACTIVE]);
    }
}