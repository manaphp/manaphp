<?php
namespace ManaPHP\Counter {

    interface AdapterInterface
    {
        public function _increment($key, $step);

        public function _delete($key);
    }
}