<?php
namespace ManaPHP\Bos;

interface ClientInterface
{
    /**
     * @param array $params
     *
     * @return array
     */
    public function createBucket($params);

    /**
     * @return array
     */
    public function listBuckets();

    /**
     * @param array $params
     *
     * @return array
     */
    public function listObjects($params = []);

    /**
     * @param $params
     * @param $file
     *
     * @return array
     */
    public function putObject($params, $file);

    /**
     * @param array $policy
     * @param int   $ttl
     *
     * @return string
     */
    public function createUploadToken($policy, $ttl = 3600);

    /**
     * verify token of create object response
     *
     * @param string $token
     *
     * @return array
     */
    public function getUploadResult($token);

    /**
     * alias of putObjectByFile
     *
     * @param $params
     * @param $file
     *
     * @return array
     */
    public function upload($params, $file);
}