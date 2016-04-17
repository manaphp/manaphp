<?php

namespace ManaPHP\Http\Session {

    use ManaPHP\Component;
    use ManaPHP\Di;

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
         * @var array
         */
        protected $_data;

        /**
         * @var bool
         */
        protected $_initialized = false;

        /**
         * @var \ManaPHP\Http\SessionInterface
         */
        protected $_session;

        /**
         * \ManaPHP\Session\Bag constructor
         *
         * @param string $name
         */
        public function __construct($name)
        {
            $this->_name = $name;
        }

        /**
         * Initializes the session bag.
         * @throws \ManaPHP\Di\Exception
         */
        protected function _initialize()
        {
            if ($this->_session === null) {
                $this->_dependencyInjector = $this->_dependencyInjector ?: Di::getDefault();

                $this->_session = $this->_dependencyInjector->getShared('session');
            }

            $this->_data = $this->_session->get($this->_name, []);

            $this->_initialized = true;
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
            $this->_initialized or $this->_initialize();

            $this->_session->remove($this->_name);
        }

        /**
         * Sets a value in the session bag
         *
         *<code>
         * $user->set('name', 'Kimbra');
         *</code>
         *
         * @param string $property
         * @param string $value
         *
         * @throws \ManaPHP\Di\Exception
         */
        public function set($property, $value)
        {
            $this->_initialized or $this->_initialize();

            $this->_data[$property] = $value;
            $this->_session->set($this->_name, $this->_data);
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
        public function get($property, $defaultValue = null)
        {
            $this->_initialized or $this->_initialize();

            return isset($this->_data[$property]) ? $this->_data[$property] : $defaultValue;
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
            $this->_initialized or $this->_initialize();

            return isset($this->_data[$property]);
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
         * @return boolean
         * @throws \ManaPHP\Di\Exception
         */
        public function remove($property)
        {
            $this->_initialized or $this->_initialize();

            unset($this->_data[$property]);

            $this->_session->set($this->_name, $this->_data);
        }
    }
}
