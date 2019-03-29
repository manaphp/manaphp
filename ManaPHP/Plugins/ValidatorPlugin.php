<?php
namespace ManaPHP\Plugins;

use ManaPHP\Plugin;

class ValidatorPlugin extends Plugin
{
    public function __construct()
    {
        $this->eventsManager->attachEvent('request:validate', [$this, 'validate']);
    }

    public function validate($source, $data)
    {
        $controller = $data['controller'];
        $validator = $controller . 'Validator';

        if (!class_exists($validator, false)) {
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