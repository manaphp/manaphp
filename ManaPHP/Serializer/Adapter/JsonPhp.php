<?php
namespace ManaPHP\Serializer\Adapter;

use ManaPHP\Serializer\Adapter\JsonPhp\Exception as JsonPhpException;
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
        if (is_scalar($data) || $data === null) {
            return true;
        } elseif (is_array($data)) {
            /** @noinspection ForeachSourceInspection */
            foreach ($data as $v) {
                if (!$this->_isCanJsonSafely($v)) {
                    return false;
                }
            }
        } else {
            return false;
        }

        return true;
    }

    /**
     * @param mixed $data
     *
     * @return string
     * @throws \ManaPHP\Serializer\Adapter\Exception
     */
    public function serialize($data)
    {
        if (is_scalar($data) || $data === null) {
            $wrappedData = ['__wrapper__' => $data];
            $serialized = json_encode($wrappedData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } elseif ($this->_isCanJsonSafely($data)) {
            $serialized = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($serialized === false) {
                throw new JsonPhpException('json_encode failed: :message'/**m0a9f602eae08a8d22*/, ['message' => json_last_error_msg()]);
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
     * @throws \ManaPHP\Serializer\Adapter\Exception
     */
    public function deserialize($serialized)
    {
        if ($serialized[0] === '{' || $serialized[0] === '[') {
            $data = json_decode($serialized, true);
            if ($data === null) {
                throw new JsonPhpException('json_encode failed: :message'/**m0e2cd70719323b2fe*/, ['message' => json_last_error_msg()]);
            }
            if (!is_array($data)) {
                throw new JsonPhpException('json serialized data is not a array, maybe it has been corrupted.'/**m01ea8122175406af1*/);
            }

            if (isset($data['__wrapper__']) && count($data) === 1) {
                return $data['__wrapper__'];
            } else {
                return $data;
            }
        } else {
            $data = unserialize($serialized);
            if ($data === false) {
                throw new JsonPhpException('unserialize failed: :message'/**m05b0e54563d1303e7*/, ['message' => error_get_last()['message']]);
            } else {
                return $data;
            }
        }
    }
}