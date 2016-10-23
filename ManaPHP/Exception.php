<?php

namespace ManaPHP;

/**
 * Class ManaPHP\Exception
 *
 * @package ManaPHP
 */
class Exception extends \Exception
{
    /**
     * @var array
     */
    protected $_bind;

    /**
     * Exception constructor.
     *
     * @param string     $message
     * @param int|array  $code
     * @param \Exception $previous
     */
    public function __construct($message = '', $code = 0, \Exception $previous = null)
    {
        $tr = [];

        if (is_array($code)) {
            $this->_bind = $code;

            /** @noinspection ForeachSourceInspection */
            foreach ($code as $k => $v) {
                $tr[':' . $k] = $v;
            }
            $code = 0;
        } else {
            $this->_bind = [];
        }

        parent::__construct(strtr($message, $tr), $code, $previous);
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

    /**
     * @return array
     */
    public function getBind()
    {
        return $this->_bind;
    }
}
