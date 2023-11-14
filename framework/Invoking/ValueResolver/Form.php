<?php
declare(strict_types=1);

namespace ManaPHP\Invoking\ValueResolver;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\FormInterface;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Invoking\ObjectValueResolverInterface;
use ManaPHP\Validating\Constraint\Attribute\Required;
use ManaPHP\Validating\Constraint\Attribute\Type;
use ManaPHP\Validating\ConstraintInterface;
use ManaPHP\Validating\ValidatorInterface;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionParameter;
use ReflectionProperty;

class Form implements ObjectValueResolverInterface
{
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected ValidatorInterface $validator;

    public function resolve(ReflectionParameter $parameter, ?string $type, string $name): mixed
    {
        if (!\is_subclass_of($type, FormInterface::class)) {
            return null;
        }

        $form = new $type();

        $source = $this->request->all();
        $validation = $this->validator->beginValidate($source);

        $rClass = new ReflectionClass($form);
        foreach ($rClass->getProperties(ReflectionProperty::IS_PUBLIC) as $rProperty) {
            $field = $rProperty->getName();

            $validation->field = $field;
            $validation->value = $source[$field] ?? null;

            if (isset($source[$field])) {
                if ($attributes = $rProperty->getAttributes(Type::class, ReflectionAttribute::IS_INSTANCEOF)) {
                    $constraint = $attributes[0]->newInstance();
                } else {
                    $constraint = new Type($rProperty->getType()?->getName() ?? 'mixed');
                }

                if ($validation->validate($constraint)) {
                    $form->$field = $validation->value;
                }
            } else {
                if (!$rProperty->isInitialized($form)) {
                    $validation->validate(new Required());
                }
            }
        }

        foreach ($rClass->getProperties(ReflectionProperty::IS_PUBLIC) as $rProperty) {
            $field = $rProperty->getName();

            if ($validation->hasError($field)) {
                continue;
            }

            $validation->field = $field;
            $validation->value = $form->$field ?? null;
            $attributes = $rProperty->getAttributes(ConstraintInterface::class, ReflectionAttribute::IS_INSTANCEOF);
            foreach ($attributes as $attribute) {
                if ($validation instanceof Type) {
                    continue;
                }

                if (!$validation->validate($attribute->newInstance())) {
                    break;
                }
            }

            if (!$validation->hasError($field)) {
                $form->$field = $validation->value;
            }
        }

        $this->validator->endValidate($validation);

        return $form;
    }
}