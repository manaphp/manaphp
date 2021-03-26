<?php

namespace ManaPHP;

class Exception extends \Exception
{
    /**
     * @var array
     */
    protected $bind = [];

    /**
     * @var array
     */
    protected $json;

    /**
     * @param string|array|\Exception $message
     * @param int                     $code
     * @param \Exception              $previous
     */
    public function __construct($message = '', $code = 0, $previous = null)
    {
        if ($message instanceof \Exception) {
            $code = $message->getCode();
            $previous = $message;
            $message = $message->getMessage();
        } elseif (is_array($message)) {
            if (substr_count($message[0], '%') + 1 >= ($count = count($message)) && isset($message[$count - 1])) {
                $message = sprintf(...$message);
            } else {
                $this->bind = $message;
                $message = $message[0];
                unset($this->bind[0]);

                $tr = [];
                foreach ($this->bind as $k => $v) {
                    if (is_array($v)) {
                        $v = implode(', ', $v);
                    } elseif ($v === null || is_bool($v)) {
                        $v = json_encode($v);
                    }

                    $tr[':' . $k] = $v;
                }

                $message = strtr($message, $tr);
            }
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return 500;
    }

    /**
     * @param array|string|int $data
     *
     * @return static
     */
    public function setJson($data)
    {
        if (is_array($data)) {
            $this->json = $data;
        } elseif (is_string($data)) {
            $this->json = ['code' => 1, 'message' => $data];
        } elseif (is_int($data)) {
            $this->json = ['code' => $data, 'message' => $this->getMessage()];
        } else {
            $this->json = $data;
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getJson()
    {
        if ($this->json) {
            return $this->json;
        } else {
            $code = $this->getStatusCode();
            $message = $code === 500 ? 'Server Internal Error' : $this->getMessage();
            return ['code' => $code === 200 ? -1 : $code, 'message' => $message];
        }
    }

    /**
     * @return array
     */
    public function getBind()
    {
        return $this->bind;
    }
}
