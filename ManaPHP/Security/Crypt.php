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
    protected $_key;

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
            throw new CryptException('`mcrypt` extension is required'/**m0aa1a20cbe4572ac7*/);
        }

        if (is_string($options)) {
            $options = ['key' => $options];
        }

        $this->_key = isset($options['key']) ? $options['key'] : $this->configure->getSecretKey('crypt');

        $this->_mcrypt = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
    }

    /**
     * Encrypts a text
     *
     *<code>
     *    $encrypted = $crypt->encrypt("Ultra-secret text", "encrypt password");
     *</code>
     *
     * @param string $text
     * @param string $key
     *
     * @return string
     * @throws \ManaPHP\Security\Crypt\Exception
     */
    public function encrypt($text, $key = null)
    {
        if ($key === null) {
            $key = $this->_key;
        }

        $ivSize = mcrypt_enc_get_block_size($this->_mcrypt);
        $encryptKey = md5($key, true);

        $iv = mcrypt_create_iv($ivSize, MCRYPT_RAND);

        mcrypt_generic_init($this->_mcrypt, $encryptKey, $iv);

        return $iv . mcrypt_generic($this->_mcrypt, pack('N', strlen($text) + 16) . $text . md5($text, true));
    }

    /**
     * Decrypts an encrypted text
     *
     *<code>
     *    echo $crypt->decrypt($encrypted, "decrypt password");
     *</code>
     *
     * @param string $text
     * @param string $key
     *
     * @return string
     * @throws \ManaPHP\Security\Crypt\Exception
     */
    public function decrypt($text, $key = null)
    {
        if ($key === null) {
            $key = $this->_key;
        }

        $ivSize = mcrypt_enc_get_block_size($this->_mcrypt);

        if (strlen($text) < $ivSize * 3) {
            throw new CryptException('encrypted data is too short.'/**m0d865273c74d547bd*/);
        }

        $encryptKey = md5($key, true);

        mcrypt_generic_init($this->_mcrypt, $encryptKey, substr($text, 0, $ivSize));

        $decrypted = mdecrypt_generic($this->_mcrypt, substr($text, $ivSize));
        $length = unpack('N', $decrypted)[1];

        if ($length < 16 || 4 + $length > strlen($decrypted)) {
            throw new CryptException('decrypted data length is too short.'/**m02504a81c0e9ef2c9*/);
        }

        $decrypted = substr($decrypted, 4, $length);
        $plainText = substr($decrypted, 0, -16);

        if (md5($plainText, true) !== substr($decrypted, -16)) {
            throw new CryptException('decrypted md5 is not valid.'/**m0847f0b0d688c6457*/);
        }

        return $plainText;
    }
}