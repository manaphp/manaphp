<?php
namespace ManaPHP\Plugins;

use ManaPHP\Http\Validator\NotFoundControllerValidatorClassException;
use ManaPHP\Plugin;

class ValidatorPlugin extends Plugin
{
    /**
     * @var bool
     */
    protected $_force = false;

    /**
     * ValidatorPlugin constructor.
     *
     * @param array $options
     */
    public function __construct($options = null)
    {
        if (isset($options['force'])) {
            $this->_force = (bool)$options['force'];
        }

        $this->eventsManager->attachEvent('request:validate', [$this, 'validate']);
    }

    public function validate($source, $data)
    {
        $controller = $data['controller'];
        $validator = $controller . 'Validator';

        if (!class_exists($validator, false)) {
            if ($this->_force) {
                throw new NotFoundControllerValidatorClassException(['`:validator` controller validator class is not found', 'validator' => $validator]);
            }

            return;
        }

        $rules = get_object_vars($this->_di->getShared($validator));

        $globals = $this->request->getGlobals();

        foreach ($globals->_REQUEST as $name => $value) {
            if ($value === '' || !isset($rules[$name])) {
                continue;
            }

            $globals->_REQUEST[$name] = $this->validator->validate($name, $rules[$name], $value);
        }
    }
}