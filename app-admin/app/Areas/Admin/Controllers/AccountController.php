<?php
declare(strict_types=1);

namespace App\Areas\Admin\Controllers;

use App\Controllers\Controller;
use App\Models\Admin;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\CaptchaInterface;
use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Http\ResponseInterface;

#[Authorize('admin')]
class AccountController extends Controller
{
    #[Autowired] protected CaptchaInterface $captcha;

    public function captchaAction(): ResponseInterface
    {
        return $this->captcha->generate();
    }

    public function registerAction(string $code)
    {
        $this->captcha->verify($code);

        return Admin::fillCreate($this->request->all(), ['white_ip' => '*', 'status' => Admin::STATUS_INIT]);
    }
}