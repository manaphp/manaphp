<?php
namespace ManaPHP\Serializer\Adapter;

use ManaPHP\Serializer\AdapterInterface;

class Php implements AdapterInterface
{
    /**
     * @param mixed $data
     *
     * @return string
     */
    public function serialize($data)
    {
        if (!is_array($data)) {
            $data = ['__wrapper__' => $data];
        }

        return serialize($data);
    }

    /**
     * @param string $serialized
     *
     * @return mixed
     * @throws \ManaPHP\Serializer\Adapter\Exception
     */
    public function deserialize($serialized)
    {
        $data = unserialize($serialized);
        if ($data === false) {
            throw new Exception('unserialize failed: ' . error_get_last()['message']);
        }

        if (!is_array($data)) {
            throw new Exception('serialize serialized data has been corrupted.');
        }

        if (isset($data['__wrapper__']) && count($data) === 1) {
            return $data['__wrapper__'];
        } else {
            return $data;
        }
    }
}