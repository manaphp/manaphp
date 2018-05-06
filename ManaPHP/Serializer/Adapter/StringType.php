<?php
namespace ManaPHP\Serializer\Adapter;

use ManaPHP\Exception\InvalidFormatException;
use ManaPHP\Serializer\AdapterInterface;

/**
 * Class ManaPHP\Serializer\Adapter\StringType
 *
 * @package serializer\adapter
 */
class StringType implements AdapterInterface
{
    /**
     * @param mixed $data
     *
     * @return string
     */
    public function serialize($data)
    {
        if (is_string($data)) {
            return $data;
        } elseif ($data === false || $data === null) {
            return '';
        } else {
            throw new InvalidFormatException(['data is not a string: `:data`', 'data' => json_encode(JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
        }
    }

    /**
     * @param string $serialized
     *
     * @return mixed
     */
    public function deserialize($serialized)
    {
        return $serialized;
    }
}