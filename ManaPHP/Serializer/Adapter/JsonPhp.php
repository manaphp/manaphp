<?php
namespace ManaPHP\Serializer\Adapter;

use ManaPHP\Exception\InvalidJsonException;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\RuntimeException;
use ManaPHP\Serializer\AdapterInterface;

/**
 * Class ManaPHP\Serializer\Adapter\JsonPhp
 *
 * @package serializer\adapter
 */
class JsonPhp implements AdapterInterface
{
    /**
     * @param mixed $data
     *
     * @return bool
     */
    public function _isCanJsonSafely($data)
    {
        if (is_array($data)) {
            /** @noinspection ForeachSourceInspection */
            foreach ($data as $v) {
                if (is_scalar($v) || $v === null || $v instanceof \JsonSerializable) {
                    continue;
                }
                if (!$this->_isCanJsonSafely($v)) {
                    return false;
                }
            }
            return true;
        } elseif (is_scalar($data) || $data === null || $data instanceof \JsonSerializable) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param mixed $data
     *
     * @return string
     */
    public function serialize($data)
    {
        if (is_scalar($data) || $data === null) {
            $wrappedData = ['__wrapper__' => $data];
            $serialized = json_encode($wrappedData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } elseif ($this->_isCanJsonSafely($data)) {
            $serialized = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($serialized === false) {
                throw new RuntimeException(['json_encode failed: :message', 'message' => json_last_error_msg()]);
            }
        } else {
            $serialized = serialize($data);
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
        if ($serialized[0] === '{' || $serialized[0] === '[') {
            $data = json_decode($serialized, true);
            if ($data === null) {
                throw new InvalidJsonException(['json_encode failed: :message', 'message' => json_last_error_msg()]);
            }
            if (!is_array($data)) {
                throw new InvalidValueException('json serialized data is not a array, maybe it has been corrupted.');
            }

            if (isset($data['__wrapper__']) && count($data) === 1) {
                return $data['__wrapper__'];
            } else {
                return $data;
            }
        } else {
            $data = unserialize($serialized);
            if ($data === false) {
                throw new InvalidValueException(['unserialize failed: :message', 'message' => error_get_last()['message']]);
            } else {
                return $data;
            }
        }
    }
}