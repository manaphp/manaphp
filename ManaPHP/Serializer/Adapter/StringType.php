<?php
namespace ManaPHP\Serializer\Adapter {

    use ManaPHP\Serializer\AdapterInterface;

    class StringType implements AdapterInterface
    {
        public function serialize($data, $context = null)
        {
            if (is_string($data)) {
                return $data;
            } elseif ($data === false || $data === null) {
                return '';
            } else {
                throw new Exception('data is not string: ' . gettype($data));
            }
        }

        public function deserialize($serialized, $content = null)
        {
            return $serialized;
        }
    }
}