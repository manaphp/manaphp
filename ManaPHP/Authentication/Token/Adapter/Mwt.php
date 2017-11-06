<?php

namespace ManaPHP\Authentication\Token\Adapter;

use ManaPHP\Authentication\Token\Adapter\Mwt\Exception as MwtException;
use ManaPHP\Authentication\TokenInterface;
use ManaPHP\Component;
use ManaPHP\Utility\Text;

/**
 * Class ManaPHP\Authentication\Token\Adapter\Mwt
 *
 * @package token\adapter
 */
class Mwt extends Component implements TokenInterface
{
    /**
     * @var string
     */
    protected $_type = 1;

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
     * @param array $options
     */
    public function __construct($options = [])
    {
        foreach (get_object_vars($this) as $field => $_) {
            if (!Text::startsWith($field, '_')) {
                $this->_fields[] = $field;
            }
        }

        if (isset($options['type'])) {
            $this->_type = $options['type'];
        }

        $this->_keys = isset($options['keys']) ? $options['keys'] : [$this->crypt->getDerivedKey('mwt:' . $this->_type)];

        if (isset($options['ttl'])) {
            $this->_ttl = $options['ttl'];
        }
    }

    /**
     * @return string
     */
    public function encode()
    {
        $data = [];

        $data['SALT'] = mt_rand(0, 2147483647);
        $data['EXP'] = $this->_ttl + time();
        foreach ($this->_fields as $k => $v) {
            $data[$v] = $this->{is_int($k) ? $v : $k};
        }

        $payload = rtrim(base64_encode(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)), '=');
        $hash = rtrim(base64_encode(md5($this->_type . $payload . $this->_keys[0], true)), '=');

        return strtr($this->_type . '.' . $payload . '.' . $hash, '+/', '-_');
    }

    /**
     * @param string $str
     *
     * @return static
     * @throws \ManaPHP\Authentication\Token\Adapter\Exception
     */
    public function decode($str)
    {
        $parts = explode('.', strtr($str, '-_', '+/'));
        if (count($parts) !== 3) {
            throw new MwtException('`:token` is not contain 3 parts'/**m0b5ce4741348c3747*/, ['token' => $str]);
        }
        list($type, $payload, $hash) = $parts;

        $success = false;
        /** @noinspection ForeachSourceInspection */
        foreach ($this->_keys as $key) {
            if (rtrim(base64_encode(md5($type . $payload . $key, true)), '=') === $hash) {
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

        $data = json_decode(base64_decode($payload), true);
        if (!is_array($data)) {
            throw new MwtException('payload is not array.'/**m02e36efb31ed0db24*/);
        }

        if (!isset($data['EXP']) || time() > $data['EXP']) {
            throw new MwtException('token is expired.'/**m0b57c4265f54099b0*/, Exception::CODE_EXPIRE);
        }

        foreach ($this->_fields as $k => $v) {
            $this->{is_int($k) ? $v : $k} = $data[$v];
        }

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->encode();
    }
}