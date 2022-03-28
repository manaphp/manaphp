<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Component;
use ManaPHP\Http\Captcha\InvalidCaptchaException;

/**
 * @property-read \ManaPHP\AliasInterface         $alias
 * @property-read \ManaPHP\Http\ResponseInterface $response
 * @property-read \ManaPHP\Http\RequestInterface  $request
 * @property-read \ManaPHP\Http\SessionInterface  $session
 */
abstract class AbstractCaptcha extends Component implements CaptchaInterface
{
    protected string $charset = '23456789abcdefhijkmnpqrstuvwxyzABCDEFGHJKLMNPQRTUVWXY';
    protected array $fonts = [];
    protected string $sessionVar = 'captcha';
    protected int $angleAmplitude = 30;
    protected int $noiseCharCount = 1;
    protected string $bgRGB = '255,255,255';
    protected int $length = 4;
    protected int $minInterval = 1;

    public function __construct(array $options = [])
    {
        if (isset($options['charset'])) {
            $this->charset = $options['charset'];
        }

        $options['fonts'] ??= [
            '@manaphp/Http/Captcha/Fonts/AirbusSpecial.ttf',
            '@manaphp/Http/Captcha/Fonts/StencilFour.ttf',
            '@manaphp/Http/Captcha/Fonts/SpicyRice.ttf'
        ];

        $this->fonts = $options['fonts'];

        if (isset($options['length'])) {
            $this->length = $options['length'];
        }

        if (isset($options['bgRGB'])) {
            $this->bgRGB = $options['bgRGB'];
        }
    }

    public function setNoiseCharCount(int $count): static
    {
        $this->noiseCharCount = $count;

        return $this;
    }

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

    public function verify(?string $code = null, bool $isTry = false): void
    {
        if ($code === null) {
            $code = $this->request->get('code');
        }

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