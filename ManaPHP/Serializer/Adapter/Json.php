<?php
namespace ManaPHP\Serializer\Adapter;

use ManaPHP\Exception\InvalidJsonException;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\RuntimeException;
use ManaPHP\Serializer\AdapterInterface;

/**
 * Class ManaPHP\Serializer\Adapter\Json
 *
 * @package serializer\adapter
 */
class Json implements AdapterInterface
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

        $serialized = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($serialized === false) {
            throw new RuntimeException(['json_encode failed: :message'/**m00e71e702b60675c8*/, 'message' => json_last_error_msg()]);
        }

        return $serialized;
    }

    /**
     * @param string $serialized
     *
     * @return mixed
     */
    public function deserialize($serialized)
    {
        $data = json_decode($serialized, true);
        if ($data === null) {
            throw new InvalidJsonException(['json_encode failed: :message'/**m08965457cf85e81eb*/, 'message' => json_last_error_msg()]);
        }

        if (!is_array($data)) {
            throw new InvalidValueException('json serialized data is not a array, maybe it has been corrupted.'/**m0e320c4b7e49fcb54*/);
        }

        if (isset($data['__wrapper__']) && count($data) === 1) {
            return $data['__wrapper__'];
        } else {
            return $data;
        }
    }
}