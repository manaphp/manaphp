<?php

namespace App\Areas\User\Controllers;

use App\Controllers\Controller;
use App\Models\User;
use ManaPHP\Http\Controller\Attribute\Authorize;

/**
 * @property-read \ManaPHP\Http\CaptchaInterface $captcha
 */
#[Authorize('*')]
class AccountController extends Controller
{
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