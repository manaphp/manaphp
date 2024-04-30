<?php
declare(strict_types=1);

namespace ManaPHP\Security;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Security\Crypt\Exception as CryptException;
use function strlen;

class Crypt implements CryptInterface
{
    #[Autowired] protected string $master_key;
    #[Autowired] protected string $method = 'AES-128-CBC';

    public function encrypt(string $text, string $key): string
    {
        $iv_length = openssl_cipher_iv_length($this->method);
        /** @noinspection CryptographicallySecureRandomnessInspection */
        if (!$iv = openssl_random_pseudo_bytes($iv_length)) {
            throw new CryptException('generate iv failed');
        }

        $data = pack('N', strlen($text)) . $text . md5($text, true);
        return $iv . openssl_encrypt($data, $this->method, md5($key, true), OPENSSL_RAW_DATA, $iv);
    }

    public function decrypt(string $text, string $key): string
    {
        $iv_length = openssl_cipher_iv_length($this->method);

        if (strlen($text) < $iv_length * 2) {
            throw new CryptException('encrypted data is too short.');
        }

        $data = substr($text, $iv_length);
        $iv = substr($text, 0, $iv_length);
        $decrypted = openssl_decrypt($data, $this->method, md5($key, true), OPENSSL_RAW_DATA, $iv);

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

    public function getDerivedKey(string $type): string
    {
        return md5($this->master_key . ':' . $type);
    }
}