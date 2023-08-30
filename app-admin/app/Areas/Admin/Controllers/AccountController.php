<?php
declare(strict_types=1);

namespace App\Areas\Admin\Controllers;

use App\Controllers\Controller;
use App\Models\Admin;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Http\CaptchaInterface;
use ManaPHP\Http\Controller\Attribute\Authorize;

#[Authorize('admin')]
class AccountController extends Controller
{
    #[Inject] protected CaptchaInterface $captcha;

    public function captchaAction()
    {
        return $this->captcha->generate();
    }

    public function registerAction()
    {
        $this->captcha->verify();

        return (new Admin)->save(
            ['admin_name', 'email', 'password', 'white_ip' => '*', 'status' => Admin::STATUS_INIT]
        );
    }
}