<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\InvokerInterface as DiInvokerInterface;
use ManaPHP\Validating\Validator\ValidateFailedException;
use ManaPHP\Validating\ValidatorInterface;
use ReflectionMethod;

class Invoker implements InvokerInterface
{
    #[Inject] protected RequestInterface $request;
    #[Inject] protected ValidatorInterface $validator;
    #[Inject] protected DiInvokerInterface $invoker;

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