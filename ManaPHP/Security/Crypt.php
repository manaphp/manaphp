<?php

namespace ManaPHP\Security;

use ManaPHP\Component;
use ManaPHP\Security\Crypt\Exception as CryptException;

/**
 * Class ManaPHP\Security\Crypt
 *
 * @package crypt
 */
class Crypt extends Component implements CryptInterface
{
    /**
     * @var string
     */
    protected $_masterKey;

    /**
     * @var resource
     */
    protected $_mcrypt;

    /**
     * Crypt constructor.
     *
     * @param string|array $options
     *
     * @throws \ManaPHP\Security\Crypt\Exception
     */
    public function __construct($options = [])
    {
        if (!extension_loaded('mcrypt')) {
            throw new CryptException('`mcrypt` extension is required');
        }

        if (is_string($options)) {
            $options = ['masterKey' => $options];
        }

        if (isset($options['masterKey'])) {
            $this->_masterKey = $options['masterKey'];
        }

        $this->_mcrypt = @mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
    }

    /**
     * Encrypts a text
     *
     * @param string $text
     * @param string $key
     *
     * @return string
     */
    public function encrypt($text, $key)
    {
        $ivSize = mcrypt_enc_get_block_size($this->_mcrypt);
        $encryptKey = md5($key, true);

        /** @noinspection CryptographicallySecureRandomnessInspection */
        $iv = mcrypt_create_iv($ivSize, MCRYPT_DEV_URANDOM);

        mcrypt_generic_init($this->_mcrypt, $encryptKey, $iv);

        return $iv . mcrypt_generic($this->_mcrypt, pack('N', strlen($text) + 16) . $text . md5($text, true));
    }

    /**
     * Decrypts an encrypted text
     *
     * @param string $text
     * @param string $key
     *
     * @return string
     * @throws \ManaPHP\Security\Crypt\Exception
     */
    public function decrypt($text, $key)
    {
        $ivSize = mcrypt_enc_get_block_size($this->_mcrypt);

        if (strlen($text) < $ivSize * 3) {
            throw new CryptException('encrypted data is too short.');
        }

        $encryptKey = md5($key, true);

        mcrypt_generic_init($this->_mcrypt, $encryptKey, substr($text, 0, $ivSize));

        $decrypted = mdecrypt_generic($this->_mcrypt, substr($text, $ivSize));
        $length = unpack('N', $decrypted)[1];

        if ($length < 16 || 4 + $length > strlen($decrypted)) {
            throw new CryptException('decrypted data length is too short.');
        }

        $decrypted = substr($decrypted, 4, $length);
        $plainText = substr($decrypted, 0, -16);

        if (md5($plainText, true) !== substr($decrypted, -16)) {
            throw new CryptException('decrypted md5 is not valid.');
        }

        return $plainText;
    }

    /**
     * @param string $key
     *
     * @return static
     */
    public function setMasterKey($key)
    {
        $this->_masterKey = $key;

        return $this;
    }

    /**
     * @param string $type
     *
     * @return string
     * @throws \ManaPHP\Security\Crypt\Exception
     */
    public function getDerivedKey($type)
    {
        if ($this->_masterKey === null) {
            throw new CryptException(['getDerivedKey for `:type` type Failed: master key is not set', 'type' => $type]);
        }

        return md5($this->_masterKey . ':' . $type);
    }
}