<?php
namespace ManaPHP {

    use ManaPHP\Counter\AdapterInterface;

    abstract class Counter extends Component implements CounterInterface, AdapterInterface
    {

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
            return $this->_delete($key);
        }
    }
}