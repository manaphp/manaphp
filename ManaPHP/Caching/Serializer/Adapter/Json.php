<?php
namespace ManaPHP\Caching\Serializer\Adapter {

    use ManaPHP\Caching\Serializer\AdapterInterface;

    class Json implements AdapterInterface
    {
        public function serialize($data, $context = null)
        {
            if (!is_array($data)) {
                $data = ['__wrapper__' => $data];
            }

            $serialized = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($serialized === false) {
                throw new Exception('json_encode failed: ' . json_last_error_msg());
            }

            return $serialized;
        }

        public function deserialize($serialized, $content = null)
        {
            $data = json_decode($serialized, true);
            if ($data === null) {
                throw new Exception('json_encode failed: ' . json_last_error_msg());
            }

            if (!is_array($data)) {
                throw new Exception('json serialized data has been corrupted.');
            }

            if (isset($data['__wrapper__']) && count($data) === 1) {
                return $data['__wrapper__'];
            } else {
                return $data;
            }
        }
    }
}