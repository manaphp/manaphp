<?php
namespace ManaPHP\Security;

use ManaPHP\Component;

class Secint extends Component implements SecintInterface
{
    /**
     * @var string
     */
    protected $_key;

    /**
     * @var array
     */
    protected $_keys = [];

    /**
     * Secint constructor.
     *
     * @param string|array $options
     */
    public function __construct($options = [])
    {
        if (is_string($options)) {
            $options = ['key' => $options];
        }

        $this->_key = isset($options['key']) ? $options['key'] : $this->configure->getSecretKey('secint');
    }

    /**
     * Encodes a variable number of parameters to generate a hash.
     *
     * @param int    $id
     * @param string $type
     *
     * @return string the generated hash
     */
    public function encode($id, $type = '')
    {
        if (!isset($this->_keys[$type])) {
            $this->_keys[$type] = md5($this->_key . $type, true);
        }

        while (true) {
            $rand = mt_rand() & 0xFFFF0000;
            $r = base64_encode(mcrypt_encrypt(MCRYPT_XTEA, $this->_keys[$type], pack('VV', $id, $rand), MCRYPT_MODE_ECB));
            if (strcspn($r, '+/') === 12) {
                return substr($r, 0, -1);
            }
        }

        return $id;
    }

    /**
     * Decodes a hash to the original parameter values.
     *
     * @param string $hash the hash to decode
     * @param string $type
     *
     * @return int|false
     */
    public function decode($hash, $type = '')
    {
        if (strlen($hash) !== 11) {
            return false;
        }

        if (!isset($this->_keys[$type])) {
            $this->_keys[$type] = md5($this->_key . $type, true);
        }

        $r = unpack('Vid/Vrand', mcrypt_decrypt(MCRYPT_XTEA, $this->_keys[$type], base64_decode($hash . '='), MCRYPT_MODE_ECB));

        if ($r['rand'] & 0xFFFF) {
            return false;
        } else {
            return $r['id'];
        }
    }
}