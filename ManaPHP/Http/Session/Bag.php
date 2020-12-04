<?php

namespace ManaPHP\Http\Session;

use ManaPHP\Component;

/**
 * @property-read \ManaPHP\Http\SessionInterface $session
 */
class Bag extends Component implements BagInterface
{
    /**
     * @var string
     */
    protected $_name;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->_name = $name;
    }

    /**
     * Destroys the session bag
     *
     * @return void
     */
    public function destroy()
    {
        $this->session->remove($this->_name);
    }

    /**
     * Sets a value in the session bag
     *
     * @param string $property
     * @param mixed  $value
     *
     * @return void
     */
    public function set($property, $value)
    {
        $defaultCurrentValue = [];
        $data = $this->session->get($this->_name, $defaultCurrentValue);
        $data[$property] = $value;

        $this->session->set($this->_name, $data);
    }

    /**
     * Obtains a value from the session bag optionally setting a default value
     *
     * @param string $property
     * @param string $default
     *
     * @return mixed
     */
    public function get($property = null, $default = null)
    {
        $defaultCurrentValue = [];
        $data = $this->session->get($this->_name, $defaultCurrentValue);

        if ($property === null) {
            return $data;
        } else {
            return $data[$property] ?? $default;
        }
    }

    /**
     * Check whether a property is defined in the internal bag
     *
     * @param string $property
     *
     * @return bool
     */
    public function has($property)
    {
        $defaultCurrentValue = [];
        $data = $this->session->get($this->_name, $defaultCurrentValue);

        return isset($data[$property]);
    }

    /**
     * Removes a property from the internal bag
     *
     * @param string $property
     *
     * @return void
     */
    public function remove($property)
    {
        $defaultCurrentValue = [];
        $data = $this->session->get($this->_name, $defaultCurrentValue);
        unset($data[$property]);

        $this->session->set($this->_name, $data);
    }
}