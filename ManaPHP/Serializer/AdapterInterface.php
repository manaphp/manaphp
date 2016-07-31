<?php
namespace ManaPHP\Serializer;

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