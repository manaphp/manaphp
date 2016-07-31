<?php

namespace ManaPHP;

/**
 * ManaPHP\Exception
 *
 * All framework exceptions should use or extend this exception
 */
class Exception extends \Exception
{
    /**
     * @return array
     */
    public function dump()
    {
        $data['id'] = date('His') . mt_rand(10, 99);
        $data['code'] = $this->code;
        $data['message'] = $this->message;
        $data['location'] = $this->file . ':' . $this->line;
        $data['class'] = get_class($this);
        $data['trace'] = explode("\n", $this->getTraceAsString());

        $data['REQUEST_URI'] = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $data['GET'] = $_GET;
        $data['POST'] = $_POST;

        return $data;
    }
}
