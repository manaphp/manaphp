<?php
namespace ManaPHP\Caching\Serializer\Adapter {

    use ManaPHP\Caching\Serializer\AdapterInterface;

    class Php implements AdapterInterface
    {
        public function serialize($data, $context = null)
        {
            if (!is_array($data)) {
                $data = ['__wrapper__' => $data];
            }

            return serialize($data);
        }

        public function deserialize($serialized, $content = null)
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
}