<?php
namespace ManaPHP\Http\Client {

    interface AdapterInterface
    {
        public function _request($type, $url, $data, $headers, $options);
    }
}