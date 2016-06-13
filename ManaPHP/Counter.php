<?php
namespace ManaPHP {

    use ManaPHP\Counter\AdapterInterface;

    abstract class Counter extends Component implements CounterInterface, AdapterInterface
    {
        public function get($key)
        {
            return $this->_get($key);
        }

        public function increment($key, $step = 1)
        {
            return $this->_increment($key, $step);
        }

        public function decrement($key, $step = 1)
        {
            return $this->_increment($key, -$step);
        }

        public function delete($key)
        {
            $this->_delete($key);
        }
    }
}