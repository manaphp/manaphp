<?php
declare(strict_types=1);

namespace App\Areas\Admin\Controllers;

use App\Controllers\Controller;
use App\Models\Admin;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\CaptchaInterface;
use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Http\ResponseInterface;
use ManaPHP\Http\Router\Attribute\GetMapping;
use ManaPHP\Http\Router\Attribute\PostMapping;
use ManaPHP\Http\Router\Attribute\RequestMapping;
use ManaPHP\Mvc\View\Attribute\View;

#[Authorize('admin')]
#[RequestMapping('/admin/account')]
class AccountController extends Controller
{
    #[Autowired] protected CaptchaInterface $captcha;

    #[PostMapping]
    public function captchaAction(): ResponseInterface
    {
        return $this->captcha->generate();
    }

    #[View]
    #[GetMapping, PostMapping]
    public function registerAction(string $code, string $password)
    {
        $this->captcha->verify($code);

        return Admin::fillCreate(
            $this->request->all(),
            ['white_ip' => '*', 'status' => Admin::STATUS_INIT, 'password' => $password]
        );
    }
}