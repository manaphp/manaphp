<?php

namespace ManaPHP\Http\Session;

use ManaPHP\Component;

/**
 * ManaPHP\Http\Session\Bag
 *
 * This component helps to separate session data into "namespaces". Working by this way
 * you can easily create groups of session variables into the application
 *
 *<code>
 *    $user = new \ManaPHP\Session\Bag('user');
 *    $user->set('name',"Kimbra Johnson");
 *    $user->set('age', 22);
 *</code>
 */
class Bag extends Component implements BagInterface
{
    /**
     * @var string
     */
    protected $_name;

    /**
     * \ManaPHP\Session\Bag constructor
     *
     * @param string $name
     *
     * @throws \ManaPHP\Di\Exception
     */
    public function __construct($name)
    {
        $this->_name = $name;
    }

    /**
     * Destroys the session bag
     *
     *<code>
     * $user->destroy();
     *</code>
     *
     * @throws \ManaPHP\Di\Exception
     */
    public function destroy()
    {
        $this->session->remove($this->_name);
    }

    /**
     * Sets a value in the session bag
     *
     *<code>
     * $user->set('name', 'Kimbra');
     *</code>
     *
     * @param string $property
     * @param mixed  $value
     *
     * @throws \ManaPHP\Di\Exception
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
     *<code>
     * echo $user->get('name', 'Kimbra');
     *</code>
     *
     * @param string $property
     * @param string $defaultValue
     *
     * @return mixed
     *
     * @throws \ManaPHP\Di\Exception
     */
    public function get($property = null, $defaultValue = null)
    {
        $defaultCurrentValue = [];
        $data = $this->session->get($this->_name, $defaultCurrentValue);

        if ($property === null) {
            return $data;
        } else {
            return isset($data[$property]) ? $data[$property] : $defaultValue;
        }
    }

    /**
     * Check whether a property is defined in the internal bag
     *
     *<code>
     * var_dump($user->has('name'));
     *</code>
     *
     * @param string $property
     *
     * @return boolean
     * @throws \ManaPHP\Di\Exception
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
     *<code>
     * $user->remove('name');
     *</code>
     *
     * @param string $property
     *
     * @return void
     * @throws \ManaPHP\Di\Exception
     */
    public function remove($property)
    {
        $defaultCurrentValue = [];
        $data = $this->session->get($this->_name, $defaultCurrentValue);
        unset($data[$property]);

        $this->session->set($this->_name, $data);
    }

    /**
     * @return array
     */
    public function dump()
    {
        $defaultCurrentValue = [];

        $data = parent::dump();
        $data['_data'] = $this->session->get($this->_name, $defaultCurrentValue);

        return $data;
    }
}