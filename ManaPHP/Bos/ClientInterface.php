<?php

namespace ManaPHP\Bos;

interface ClientInterface
{
    /**
     * @param string $bucket
     * @param string $base_url
     *
     * @return array
     */
    public function createBucket($bucket, $base_url = null);

    /**
     * @return array
     */
    public function listBuckets();

    /**
     * @param string $bucket
     * @param array  $filters
     *
     * @return array
     */
    public function listObjects($bucket, $filters = []);

    /**
     * @param string $file
     * @param string $bucket
     * @param string $key
     * @param array  $policy
     *
     * @return array
     */
    public function putObject($file, $bucket, $key, $policy = []);

    /**
     * @param string $bucket
     * @param string $key
     * @param array  $policy
     * @param int    $ttl
     *
     * @return string
     */
    public function getPutObjectUrl($bucket, $key, $policy = [], $ttl = 3600);

    /**
     * @param string $token
     *
     * @return array
     */
    public function parsePutObjectResponse($token);
}