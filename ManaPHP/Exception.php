<?php

namespace ManaPHP {

    /**
     * ManaPHP\Exception
     *
     * All framework exceptions should use or extend this exception
     */
    class Exception extends \Exception
    {
        public function dump()
        {
            $data = get_object_vars($this);
            $data['trace'] = explode("\n", $this->getTraceAsString());
            return $data;
        }
    }
}
