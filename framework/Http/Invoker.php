<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Component;
use ManaPHP\Validating\Validator\ValidateFailedException;
use ReflectionMethod;

/**
 * @property-read \ManaPHP\Http\RequestInterface         $request
 * @property-read \ManaPHP\Validating\ValidatorInterface $validator
 * @property-read \ManaPHP\Di\InvokerInterface           $invoker
 */
class Invoker extends Component implements InvokerInterface
{
    public function invoke(Controller $controller, string $method): mixed
    {
        $args = [];
        $missing = [];

        $rMethod = new ReflectionMethod($controller, $method);
        $rParameters = $rMethod->getParameters();
        foreach ($rParameters as $rParameter) {
            if (($rType = $rParameter->getType()) !== null && !$rType->isBuiltin()) {
                continue;
            }

            $name = $rParameter->getName();

            if (!$this->request->has($name)) {
                if (!$rParameter->isDefaultValueAvailable()) {
                    $missing[] = $name;
                }
                continue;
            }

            $value = $this->request->get($name);

            if (($rType = $rParameter->getType()) !== null) {
                $type = $rType->getName();
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

        return $this->invoker->call([$controller, $method], $args);
    }
}