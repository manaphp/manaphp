<?php

namespace ManaPHP\Security;

/**
 * Interface ManaPHP\Security\CryptInterface
 *
 * @package ManaPHP\Security
 */
interface CryptInterface
{

    /**
     * Encrypts a text
     *
     * @param string $text
     * @param string $key
     *
     * @return string
     */
    public function encrypt($text, $key = null);

    /**
     * Decrypts a text
     *
     * @param string $text
     * @param string $key
     *
     * @return string
     */
    public function decrypt($text, $key = null);
}