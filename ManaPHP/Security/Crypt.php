<?php

namespace ManaPHP\Security;

use ManaPHP\Component;
use ManaPHP\Security\Crypt\Exception;

/**
 * ManaPHP\Crypt
 *
 * Provides encryption facilities to ManaPHP applications
 *
 *<code>
 *    $crypt = new ManaPHP\Crypt();
 *
 *    $key = 'le password';
 *    $text = 'This is a secret text';
 *
 *    $encrypted = $crypt->encrypt($text, $key);
 *
 *    echo $crypt->decrypt($encrypted, $key);
 *</code>
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
     * @param string $key
     *
     * @throws \ManaPHP\Security\Crypt\Exception
     */
    public function __construct($key = null)
    {
        if (!extension_loaded('mcrypt')) {
            throw new Exception('mcrypt extension is required');
        }

        $this->_key = $key;

        $this->_mcrypt = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
    }

    /**
     * Encrypts a text
     *
     *<code>
     *    $encrypted = $crypt->encrypt("Ultra-secret text", "encrypt password");
     *</code>
     *
     * @param string      $text
     * @param string|null $key
     *
     * @return string
     * @throws \ManaPHP\Security\Crypt\Exception
     */
    public function encrypt($text, $key = null)
    {
        if ($key === null) {
            $key = $this->_key;
        }

        if ($key === null) {
            throw new Exception('Encryption key cannot be empty');
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
     * @param string      $text
     * @param string|null $key
     *
     * @return string
     * @throws \ManaPHP\Security\Crypt\Exception
     */
    public function decrypt($text, $key = null)
    {
        if ($key === null) {
            $key = $this->_key;
        }

        if ($key === null) {
            throw new Exception('Encryption key cannot be empty');
        }

        $ivSize = mcrypt_enc_get_block_size($this->_mcrypt);

        if (strlen($text) < $ivSize * 3) {
            throw new Exception('Encrypted data is too short.');
        }

        $encryptKey = md5($key, true);

        mcrypt_generic_init($this->_mcrypt, $encryptKey, substr($text, 0, $ivSize));

        $decrypted = mdecrypt_generic($this->_mcrypt, substr($text, $ivSize));
        $length = unpack('N', $decrypted)[1];

        if ($length < 16 || 4 + $length > strlen($decrypted)) {
            throw new Exception('Decrypted data length is too short.');
        }

        $decrypted = substr($decrypted, 4, $length);
        $plainText = substr($decrypted, 0, -16);

        if (md5($plainText, true) !== substr($decrypted, -16)) {
            throw new Exception('decrypted md5 is not valid.');
        }

        return $plainText;
    }
}