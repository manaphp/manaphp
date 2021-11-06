<?php

namespace ManaPHP\Http;

use ManaPHP\Component;
use ManaPHP\Validating\Validator\ValidateFailedException;
use ManaPHP\Helper\Reflection;

/**
 * @property-read \ManaPHP\Http\RequestInterface         $request
 * @property-read \ManaPHP\Validating\ValidatorInterface $validator
 */
class Invoker extends Component implements InvokerInterface
{
    /**
     * @param \ManaPHP\Http\Controller $controller
     * @param string                   $method
     *
     * @return mixed
     */
    public function invoke($controller, $method)
    {
        $args = [];
        $missing = [];

        $rParameters = Reflection::reflectMethod($controller, $method)->getParameters();
        foreach ($rParameters as $rParameter) {
            if ($rParameter->hasType() && !$rParameter->getType()->isBuiltin()) {
                continue;
            }

            $name = $rParameter->getName();

            if (!$this->request->has($name)) {
                if ($rParameter->isDefaultValueAvailable()) {
                    continue;
                } else {
                    $missing[] = $name;
                    continue;
                }
            }

            $value = $this->request->get($name);

            if ($rParameter->hasType()) {
                $type = $rParameter->getType()->getName();
            } elseif ($rParameter->isDefaultValueAvailable()) {
                $type = gettype($rParameter->getDefaultValue());
            } else {
                $type = 'NULL';
            }

            if ($type !== 'NULL') {
                $value = $this->validator->validateValue($name, $value, [$type]);
            }

            $args[$name] = $value;
        }

        if ($missing) {
            $errors = [];
            foreach ($missing as $field) {
                $errors[$field] = $this->validator->createError('required', $field);
            }
            throw new ValidateFailedException($errors);
        }

        return $this->container->call([$controller, $method], $args);
    }
}