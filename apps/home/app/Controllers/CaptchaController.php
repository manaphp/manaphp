<?php
namespace App\Home\Controllers;

class CaptchaController extends ControllerBase
{
    public function newAction()
    {
        return $this->captcha->generate();
    }
}