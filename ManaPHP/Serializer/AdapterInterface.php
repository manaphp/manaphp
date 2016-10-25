<?php
namespace ManaPHP\Serializer;

/**
 * Interface ManaPHP\Serializer\AdapterInterface
 *
 * @package serializer
 */
interface AdapterInterface
{
    /**
     * @param mixed $data
     *
     * @return string
     */
    public function serialize($data);

    /**
     * @param string $serialized
     *
     * @return mixed
     */
    public function deserialize($serialized);
}