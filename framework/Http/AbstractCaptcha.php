<?php

declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\Captcha\InvalidCaptchaException;
use function random_int;
use function strlen;
use function strtolower;
use function time;

abstract class AbstractCaptcha implements CaptchaInterface
{
    #[Autowired] protected ResponseInterface $response;
    #[Autowired] protected SessionInterface $session;

    /** @noinspection SpellCheckingInspection */
    #[Autowired] protected string $charset = '23456789abcdefhijkmnpqrstuvwxyzABCDEFGHJKLMNPQRTUVWXY';
    #[Autowired] protected array $fonts
        = [
            '@manaphp/Http/Captcha/Fonts/AirbusSpecial.ttf',
            '@manaphp/Http/Captcha/Fonts/StencilFour.ttf',
            '@manaphp/Http/Captcha/Fonts/Vera.ttf'
        ];
    #[Autowired] protected string $sessionVar = 'captcha';
    #[Autowired] protected int $angle_noise = 10;
    #[Autowired] protected int $x_noise = 3;
    #[Autowired] protected int $y_noise = 3;
    #[Autowired] protected int $size = 24;
    #[Autowired] protected int $size_noise = 3;
    #[Autowired] protected int $char_noise = 2;
    #[Autowired] protected string $bg_rgb = '255,255,255';
    #[Autowired] protected int $length = 4;
    #[Autowired] protected int $min_interval = 1;

    protected function rand_amplitude(float $a): float
    {
        return random_int((int)((1 - $a) * 10000), (int)((1 + $a) * 10000)) / 10000;
    }

    public function generate(int $width = 100, int $height = 30, int $ttl = 300): ResponseInterface
    {
        $code = '';
        $charsetCount = strlen($this->charset);
        for ($i = 0; $i < $this->length; $i++) {
            $code .= $this->charset[random_int(0, $charsetCount - 1)];
        }

        $content = $this->draw($code, $width, $height);
        $this->response->setContentType('image/jpeg');
        $this->response->setContent($content);

        $captchaData = ['code' => $code, 'created_time' => time(), 'ttl' => $ttl];
        $this->session->set($this->sessionVar, $captchaData);

        return $this->response;
    }

    public function verify(string $code, bool $isTry = false): void
    {
        if (!$this->session->has($this->sessionVar)) {
            throw new InvalidCaptchaException('Captcha does not exist in server session.', ['session_var' => $this->sessionVar]);
        }

        $sessionVar = $this->session->get($this->sessionVar);

        if ($isTry) {
            if (isset($sessionVar['try_verified_time'])) {
                $this->session->remove($this->sessionVar);
                throw new InvalidCaptchaException('Captcha has already been tried once.');
            } else {
                $sessionVar['try_verified_time'] = time();
                $this->session->set($this->sessionVar, $sessionVar);
            }
        } else {
            $this->session->remove($this->sessionVar);
        }

        $timeSinceCreation = time() - $sessionVar['created_time'];
        if ($timeSinceCreation < $this->min_interval) {
            throw new InvalidCaptchaException('Captcha verification attempts are too frequent.', ['time_since_creation' => $timeSinceCreation, 'min_interval' => $this->min_interval, 'created_time' => date('Y-m-d H:i:s', $sessionVar['created_time'])]);
        }

        if ($timeSinceCreation > $sessionVar['ttl']) {
            throw new InvalidCaptchaException('Captcha has expired.', ['expired_at' => date('Y-m-d H:i:s', $sessionVar['created_time'] + $sessionVar['ttl']), 'current_time' => date('Y-m-d H:i:s'), 'ttl' => $sessionVar['ttl']]);
        }

        if (strtolower($sessionVar['code']) !== strtolower($code)) {
            throw new InvalidCaptchaException('Captcha code does not match.', ['code' => $code]);
        }
    }
}
