<?php
namespace ManaPHP\Serializer\Adapter;

use ManaPHP\Serializer\Adapter\Json\Exception as JsonException;
use ManaPHP\Serializer\AdapterInterface;

class Json implements AdapterInterface
{
    /**
     * @param mixed $data
     *
     * @return string
     * @throws \ManaPHP\Serializer\Adapter\Exception
     */
    public function serialize($data)
    {
        if (!is_array($data)) {
            $data = ['__wrapper__' => $data];
        }

        $serialized = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($serialized === false) {
            throw new JsonException('json_encode failed: :message'/**m00e71e702b60675c8*/, ['message' => json_last_error_msg()]);
        }

        return $serialized;
    }

    /**
     * @param string $serialized
     *
     * @return mixed
     * @throws \ManaPHP\Serializer\Adapter\Exception
     */
    public function deserialize($serialized)
    {
        $data = json_decode($serialized, true);
        if ($data === null) {
            throw new JsonException('json_encode failed: :message'/**m08965457cf85e81eb*/, ['message' => json_last_error_msg()]);
        }

        if (!is_array($data)) {
            throw new JsonException('json serialized data is not a array, maybe it has been corrupted.'/**m0e320c4b7e49fcb54*/);
        }

        if (isset($data['__wrapper__']) && count($data) === 1) {
            return $data['__wrapper__'];
        } else {
            return $data;
        }
    }
}