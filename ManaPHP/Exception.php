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
     * Exception constructor.
     *
     * @param string          $message
     * @param int|array       $code
     * @param \Exception|null $previous
     */
    public function __construct($message = '', $code = 0, \Exception $previous = null)
    {
        if (is_array($code)) {
            $tr = [];

            /** @noinspection ForeachSourceInspection */
            foreach ($code as $k => $v) {
                $tr[':' . $k] = $v;
            }
            $message = strtr($message, $tr);
            $code = 0;
        }

        parent::__construct($message, $code, $previous);
    }

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

    /**
     * @return string
     */
    public static function getLastErrorMessage()
    {
        $error = error_get_last();

        return $error['message'];
    }
}
