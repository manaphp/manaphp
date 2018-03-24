<?php
namespace ManaPHP\Security;

/**
 * Interface ManaPHP\Security\CaptchaInterface
 *
 * @package captcha
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
     */
    public function verify($code = null);

    /**
     * @param string $code
     *
     * @return void
     */
    public function tryVerify($code = null);
}