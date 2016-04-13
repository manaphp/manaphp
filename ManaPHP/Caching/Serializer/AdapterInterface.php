<?php
namespace ManaPHP\Caching\Serializer {

    interface AdapterInterface
    {

        /**
         * @param       $data
         * @param array $context
         *
         * @return string
         */
        public function serialize($data, $context = null);

        /**
         * @param string $serialized
         * @param array  $content
         *
         * @return mixed
         */
        public function deserialize($serialized, $content = null);
    }
}