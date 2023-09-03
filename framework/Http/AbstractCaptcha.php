<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\AliasInterface;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Value;
use ManaPHP\Http\Captcha\InvalidCaptchaException;

abstract class AbstractCaptcha implements CaptchaInterface
{
    #[Inject] protected AliasInterface $alias;
    #[Inject] protected ResponseInterface $response;
    #[Inject] protected SessionInterface $session;

    #[Value] protected string $charset = '23456789abcdefhijkmnpqrstuvwxyzABCDEFGHJKLMNPQRTUVWXY';
    #[Value] protected array $fonts
        = [
            '@manaphp/Http/Captcha/Fonts/AirbusSpecial.ttf',
            '@manaphp/Http/Captcha/Fonts/StencilFour.ttf',
            '@manaphp/Http/Captcha/Fonts/SpicyRice.ttf'
        ];
    #[Value] protected string $sessionVar = 'captcha';
    protected int $angleAmplitude = 30;
    #[Value] protected int $noiseCharCount = 1;
    #[Value] protected string $bgRGB = '255,255,255';
    #[Value] protected int $length = 4;
    protected int $minInterval = 1;

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
            throw new InvalidCaptchaException('captcha is not exist in server');
        }

        $sessionVar = $this->session->get($this->sessionVar);

        if ($isTry) {
            if (isset($sessionVar['try_verified_time'])) {
                $this->session->remove($this->sessionVar);
                throw new InvalidCaptchaException('captcha has been tried');
            } else {
                $sessionVar['try_verified_time'] = time();
                $this->session->set($this->sessionVar, $sessionVar);
            }
        } else {
            $this->session->remove($this->sessionVar);
        }

        if (time() - $sessionVar['created_time'] < $this->minInterval) {
            throw new InvalidCaptchaException('captcha verification is too frequency');
        }

        if (time() - $sessionVar['created_time'] > $sessionVar['ttl']) {
            throw new InvalidCaptchaException('captcha is expired');
        }

        if (strtolower($sessionVar['code']) !== strtolower($code)) {
            throw new InvalidCaptchaException('captcha is not match');
        }
    }
}