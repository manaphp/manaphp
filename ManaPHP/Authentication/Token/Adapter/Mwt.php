<?php
namespace ManaPHP\Authentication\Token\Adapter;

use ManaPHP\Authentication\Token;
use ManaPHP\Authentication\Token\Adapter\Mwt\Exception as MwtException;
use ManaPHP\Authentication\TokenInterface;
use ManaPHP\Component;
use ManaPHP\Utility\Text;

class Mwt extends Component implements TokenInterface
{
    /**
     * @var string
     */
    protected $_type;

    /**
     * @var int
     */
    protected $_expired_time;

    /**
     * @var array
     */
    protected $_keys = [];

    /**
     * @var int
     */
    protected $_ttl = 86400;

    /**
     * @var array
     */
    protected $_fields = [];

    /**
     * Mwt constructor.
     *
     * @param string       $type
     * @param string|array $keys
     */
    public function __construct($type, $keys)
    {
        $this->_type = $type;

        if (is_string($keys)) {
            $keys = [$keys];
        }

        $this->_keys = (array)$keys;

        foreach (get_object_vars($this) as $field => $_) {
            if (!Text::startsWith($field, '_')) {
                $this->_fields[] = $field;
            }
        }
    }

    /**
     * @param mixed $data
     *
     * @return string
     */
    protected function _encode($data)
    {
        $r = $this->_type . '.' . base64_encode(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $hash = base64_encode(md5($r . $this->_keys[0], true));
        $r .= '.' . $hash;

        $from = ['+', '/', '='];
        $to = ['-', '_', ''];
        return str_replace($from, $to, $r);
    }

    /**
     * @param string $str
     *
     * @return mixed
     * @throws \ManaPHP\Authentication\Token\Adapter\Exception
     */
    protected function _decode($str)
    {
        $from = ['-', '_'];
        $to = ['+', '/'];
        $t = str_replace($from, $to, $str);

        $parts = explode('.', $t);
        if (count($parts) !== 3) {
            throw new MwtException('`:token` is not contain 3 parts'/**m0b5ce4741348c3747*/, ['token' => $str]);
        }

        $type = $parts[0];
        $payload = $parts[1];
        /** @noinspection MultiAssignmentUsageInspection */
        $hash = $parts[2];

        $mod4 = strlen($payload) % 4;
        $payload .= str_repeat('=', $mod4 ? 4 - $mod4 : 0);
        $mod4 = strlen($hash) % 4;
        $hash .= str_repeat('=', $mod4 ? 4 - $mod4 : 0);

        $success = false;
        foreach ($this->_keys as $key) {
            if (base64_encode(md5($type . '.' . $payload . $key, true)) !== $hash) {
                continue;
            } else {
                $success = true;
                break;
            }
        }

        if (!$success) {
            throw new MwtException('hash is not corrected: :hash'/**m0f1ae3ea6eeb35939*/, ['hash' => $hash]);
        }

        /** @noinspection TypeUnsafeComparisonInspection */
        if ($type != $this->_type) {
            throw new MwtException('type is not correct: :type'/**m09537eea529cf24a6*/, ['type' => $type]);
        }

        $r = json_decode(base64_decode($payload), true);
        if (!is_array($r)) {
            throw new MwtException('payload is not array.'/**m02e36efb31ed0db24*/);
        }

        return $r;
    }

    /**
     * @param int $ttl
     *
     * @return string
     * @throws \ManaPHP\Authentication\Token\Adapter\Exception
     */
    public function encode($ttl = 0)
    {
        $data = [];

        $data['SALT'] = mt_rand();
        $data['EXP'] = (($ttl !== 0) ? $ttl : $this->_ttl) + time();
        foreach ($this->_fields as $k => $v) {
            $valueField = is_int($k) ? $v : $k;

            if (!isset($this->{$valueField})) {
                throw new MwtException('`:field` field value is NULL', ['field' => $valueField]);
            }
            $data[$v] = $this->{$valueField};
        }

        return $this->_encode($data);
    }

    /**
     * @param string $str
     *
     * @return static
     * @throws \ManaPHP\Authentication\Token\Adapter\Exception
     */
    public function decode($str)
    {
        $data = $this->_decode($str);

        if (!isset($data['EXP']) && $data['EXP'] < time()) {
            throw new MwtException('token is expired.'/**m0b57c4265f54099b0*/, Exception::CODE_EXPIRE);
        }

        foreach ($this->_fields as $k => $v) {
            $keyField = is_int($k) ? $v : $k;

            if (!isset($data[$v])) {
                throw new MwtException('`:field` field value is not exists in payload'/**m059345500bb0de141*/, ['field' => $v]);
            }

            $this->{$keyField} = $data[$v];
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getExpiredTime()
    {
        return $this->_expired_time;
    }
}