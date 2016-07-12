<?php
namespace ManaPHP\Authentication;

use ManaPHP\Component;

class Password extends Component implements PasswordInterface
{
    /**
     * generate a salt
     *
     * @param int $length
     *
     * @return string
     */
    public function salt($length = 8)
    {
        $base64 = base64_encode(md5(mt_rand() . microtime(), true));
        if ($length > 22) {
            $base64 .= base64_encode(md5(mt_rand(), true));
        }

        return strtr(substr($base64, 0, $length), '+/', '69');
    }

    /**
     * @param string $pwd
     * @param string $salt
     *
     * @return mixed
     */
    public function hash($pwd, $salt = null)
    {
        if ($salt === null) {
            return md5($pwd);
        } else {
            return md5(md5($pwd) . $salt);
        }
    }

    /**
     * @param  string $pwd
     * @param  string $hash
     * @param  string $salt
     *
     * @return bool
     */
    public function verify($pwd, $hash, $salt = null)
    {
        return $this->hash($pwd, $salt) === $hash;
    }
}