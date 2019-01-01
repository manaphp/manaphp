<?php
namespace ManaPHP\Bos;

interface ClientInterface
{
    /**
     * create token for create object request
     *
     * @param array $policy
     * @param int   $ttl
     *
     * @return string
     */
    public function createToken($policy, $ttl = 3600);

    /**
     * verify token of create object response
     *
     * @param string $token
     *
     * @return array
     */
    public function verifyToken($token);

    /**
     * @param string $file
     * @param string $bucket
     * @param string $key
     *
     * @return array
     */
    public function upload($file, $bucket, $key);
}