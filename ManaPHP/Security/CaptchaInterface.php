<?php
namespace ManaPHP\Security;

/**
 * Interface ManaPHP\Security\CaptchaInterface
 *
 * @package ManaPHP\Security
 */
interface CaptchaInterface
{
    /**
     * @param int $width
     * @param int $height
     * @param int $ttl
     *
     * @return \ManaPHP\Http\ResponseInterface
     */
    public function generate($width = 100, $height = 30, $ttl = 300);

    /**
     * @param string $code
     *
     * @return void
     * @throws \ManaPHP\Security\Captcha\Exception
     */
    public function verify($code);

    /**
     * @param string $code
     *
     * @return void
     * @throws \ManaPHP\Security\Captcha\Exception
     */
    public function tryVerify($code);
}