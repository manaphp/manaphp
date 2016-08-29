<?php
namespace ManaPHP\Task\Metadata;

interface AdapterInterface
{
    /**
     * @param string $key
     *
     * @return mixed|false
     */
    public function _get($key);

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    public function _set($key, $value);

    /**
     * @param string $key
     *
     * @return void
     */
    public function _delete($key);

    /**
     * @param string $key
     *
     * @return bool
     */
    public function _exists($key);
}