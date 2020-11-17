<?php

namespace ManaPHP\Security;

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
    public function encrypt($text, $key);

    /**
     * Decrypts a text
     *
     * @param string $text
     * @param string $key
     *
     * @return string
     */
    public function decrypt($text, $key);

    /**
     * @param string $key
     *
     * @return static
     */
    public function setMasterKey($key);

    /**
     * @param string $type
     *
     * @return string
     */
    public function getDerivedKey($type);
}