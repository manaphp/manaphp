<?php
namespace ManaPHP\Security\Token\Adapter {

    use ManaPHP\Component;
    use ManaPHP\Security\Token;
    use ManaPHP\Security\TokenInterface;
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
        protected $_expireAt;

        /**
         * @var array
         */
        protected $_keys;

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
         * @param int          $type
         * @param string|array $keys
         */
        public function __construct($type, $keys)
        {
            parent::__construct();

            $this->_type = $type;

            if (is_string($keys)) {
                $keys = [$keys];
            }

            $this->_keys = $keys;

            foreach (get_object_vars($this) as $field => $_) {
                if (!Text::startsWith($field, '_')) {
                    $this->_fields[] = $field;
                }
            }
        }

        protected function _encode($data)
        {
            $r = $this->_type . '.' . base64_encode(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $hash = base64_encode(md5($r . $this->_keys[0], true));
            $r .= '.' . $hash;

            return str_replace(['+', '/', '='], ['-', '_', ''], $r);
        }

        protected function _decode($str)
        {
            $t = str_replace(['-', '_'], ['+', '/'], $str);

            $parts = explode('.', $t);
            if (count($parts) !== 3) {
                throw new Exception('token format is invalid: ' . $str);
            }

            list($type, $payload, $hash) = $parts;
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
                throw new Exception('hash is not corrected: ' . $hash);
            }

            if ($type != $this->_type) {
                throw new Exception('type is not correct: ' . $type);
            }

            $r = json_decode(base64_decode($payload), true);
            if (!is_array($r)) {
                throw new Exception('payload is invalid.');
            }

            return $r;
        }

        public function encode($ttl = null)
        {
            $data = [];

            $data['SALT'] = mt_rand();
            $data['EXP'] = (($ttl !== null) ? $ttl : $this->_ttl) + time();
            foreach ($this->_fields as $k => $v) {
                $valueField = is_int($k) ? $v : $k;

                if (!isset($this->{$valueField})) {
                    throw new Exception('encode failed: ' . $valueField . ' field value is NULL');
                }
                $data[$v] = $this->{$valueField};
            }

            return $this->_encode($data);
        }

        public function decode($str)
        {
            $data = $this->_decode($str);

            if (!isset($data['EXP']) && $data['EXP'] < time()) {
                throw new Exception('token is expired: ' . Exception::CODE_EXPIRE);
            }

            foreach ($this->_fields as $k => $v) {
                $keyField = is_int($k) ? $v : $k;

                if (!isset($data[$v])) {
                    throw new Exception('decode failed: ' . $v . 'field value is not exists');
                }

                $this->{$keyField} = $data[$v];
            }

            return $this;
        }

        public function getExpireAt()
        {
            return $this->_expireAt;
        }
    }
}