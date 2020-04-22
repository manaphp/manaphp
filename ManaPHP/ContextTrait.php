<?php

namespace ManaPHP;

use ManaPHP\Coroutine\Context\Inseparable;
use Swoole\Coroutine;

trait ContextTrait
{
    /**
     * @var int
     */
    protected $_object_id;

    /**
     * @var \ArrayObject
     */
    protected $_context;

    /**
     * @return object
     */
    protected function _createContext()
    {
        static $cached = [];

        $class = static::class;
        if (!$context = $cached[$class] ?? null) {
            $parent = $class;
            do {
                $try = $parent . 'Context';
                if (class_exists($try, false)) {
                    $context = $try;
                    break;
                }
            } while ($parent = get_parent_class($parent));

            $cached[$class] = $context ?: 'ArrayObject';
        }

        return new $context();
    }

    /**
     * @return mixed
     */
    protected function _getContext()
    {
        global $__root_context;

        if (!$object_id = $this->_object_id) {
            $object_id = $this->_object_id = spl_object_id($this);
        }

        if (MANAPHP_COROUTINE_ENABLED) {
            if ($context = Coroutine::getContext()) {
                if (!$object = $context[$object_id] ?? null) {
                    if (($parent_cid = Coroutine::getPcid()) === -1) {
                        return $context[$object_id] = $this->_createContext();
                    }

                    $parent_context = Coroutine::getContext($parent_cid);
                    if ($object = $parent_context[$object_id] ?? null) {
                        return $context[$object_id] = $object instanceof Inseparable ? $this->_createContext() : $object;
                    } else {
                        $object = $context[$object_id] = $this->_createContext();
                        if (!$object instanceof Inseparable) {
                            $parent_context[$object_id] = $object;
                        }
                    }
                }
                return $object;
            } elseif (!$object = $__root_context[$object_id] ?? null) {
                return $__root_context[$object_id] = $this->_createContext();
            } else {
                return $object;
            }
        } elseif (PHP_SAPI === 'cli') {
            if (!$object = $__root_context[$object_id] ?? null) {
                $__root_context[] = $this;
                return $this->_context = $this->_createContext();
            } else {
                return $object;
            }
        } else {
            return $this->_context = $this->_createContext();
        }
    }

    public function __get($name)
    {
        if ($name === '_context') {
            return $this->_getContext();
        } else {
            return $this->_context->$name;
        }
    }

    public function __set($name, $value)
    {
        $this->_context->$name = $value;
    }

    public function __isset($name)
    {
        return isset($this->_context->$name);
    }

    public function __unset($name)
    {
        unset($this->_context->$name);
    }

    public function __debugInfo()
    {
        $data = get_object_vars($this);

        if ($this->_object_id) {
            $data['_context'] = (array)$this->_context;
        }
        unset($data['_object_id']);

        return $data;
    }
}