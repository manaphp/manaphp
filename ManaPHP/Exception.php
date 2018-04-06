<?php

namespace ManaPHP;

/**
 * Class ManaPHP\Exception
 *
 * @package exception
 */
class Exception extends \Exception
{
    /**
     * @var array
     */
    protected $_bind = [];

    /**
     * Exception constructor.
     *
     * @param string|array $message
     * @param int          $code
     * @param \Exception   $previous
     */
    public function __construct($message = '', $code = 0, \Exception $previous = null)
    {
        $tr = [];

        if (is_array($message)) {
            $this->_bind = $message;
            $message = $message[0];
            unset($this->_bind[0]);
        }

        if (!isset($this->_bind['last_error_message'])) {
            $this->_bind['last_error_message'] = error_get_last()['message'];
        }
        /** @noinspection ForeachSourceInspection */
        foreach ($this->_bind as $k => $v) {
            $tr[':' . $k] = $v;
        }

        parent::__construct(strtr($message, $tr), $code, $previous);
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return 500;
    }

    /**
     * @return string
     */
    public function getStatusText()
    {
        return 'Internal Server Error';
    }

    /**
     * @return array
     */
    public function dump()
    {
        $data = [];

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
