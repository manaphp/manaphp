<?php

namespace App\Areas\User\Controllers;

use App\Controllers\Controller;
use App\Models\User;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\CaptchaInterface;
use ManaPHP\Http\Controller\Attribute\Authorize;

#[Authorize('*')]
class AccountController extends Controller
{
    #[Autowired] protected CaptchaInterface $captcha;

    public function captchaAction()
    {
        return $this->captcha->generate();
    }

    public function registerAction(string $code)
    {
        $this->captcha->verify($code);
        return User::fillCreate($this->request->all(), ['status' => User::STATUS_ACTIVE]);
    }
}