<?php
declare(strict_types=1);

namespace App\Areas\Admin\Controllers;

use App\Controllers\Controller;
use App\Models\Admin;
use ManaPHP\Http\Controller\Attribute\Authorize;

/**
 * @property-read \ManaPHP\Http\CaptchaInterface $captcha
 */
#[Authorize('admin')]
class AccountController extends Controller
{
    public function captchaAction()
    {
        return $this->captcha->generate();
    }

    public function registerAction()
    {
        $this->captcha->verify();
        return Admin::rCreate(['admin_name', 'email', 'password', 'white_ip' => '*', 'status' => Admin::STATUS_INIT]);
    }
}