<?php

namespace ManaPHP\Security;

use ManaPHP\Component;
use ManaPHP\Security\Crypt\Exception as CryptException;

class Crypt extends Component implements CryptInterface
{
    /**
     * @var string
     */
    protected $_master_key;

    /**
     * @var string
     */
    protected $_method = 'AES-128-CBC';

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['master_key'])) {
            $this->_master_key = $options['master_key'];
        }

        if (isset($options['method'])) {
            $this->_method = $options['method'];
        }
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
        $iv_length = openssl_cipher_iv_length($this->_method);
        /** @noinspection CryptographicallySecureRandomnessInspection */
        if (!$iv = openssl_random_pseudo_bytes($iv_length)) {
            throw new CryptException('generate iv failed');
        }

        $data = pack('N', strlen($text)) . $text . md5($text, true);
        return $iv . openssl_encrypt($data, $this->_method, md5($key, true), OPENSSL_RAW_DATA, $iv);
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
        $iv_length = openssl_cipher_iv_length($this->_method);

        if (strlen($text) < $iv_length * 2) {
            throw new CryptException('encrypted data is too short.');
        }

        $data = substr($text, $iv_length);
        $iv = substr($text, 0, $iv_length);
        $decrypted = openssl_decrypt($data, $this->_method, md5($key, true), OPENSSL_RAW_DATA, $iv);

        $length = unpack('N', $decrypted)[1];

        if (4 + $length + 16 !== strlen($decrypted)) {
            throw new CryptException('decrypted data length is wrong.');
        }

        $plainText = substr($decrypted, 4, -16);

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
        $this->_master_key = $key;

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
        if ($this->_master_key === null) {
            throw new CryptException(['getDerivedKey for `:type` type Failed: master key is not set', 'type' => $type]);
        }

        return md5($this->_master_key . ':' . $type);
    }
}