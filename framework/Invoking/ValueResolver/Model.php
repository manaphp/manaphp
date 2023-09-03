<?php
declare(strict_types=1);

namespace ManaPHP\Invoking\ValueResolver;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Exception\BadRequestException;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Invoking\ObjectValueResolverInterface;
use ManaPHP\Model\ModelInterface;
use ManaPHP\Model\ModelManagerInterface;
use ManaPHP\Validating\Validator\ValidateFailedException;
use ManaPHP\Validating\ValidatorInterface;
use ReflectionParameter;

class Model implements ObjectValueResolverInterface
{
    #[Inject] protected RequestInterface $request;
    #[Inject] protected ModelManagerInterface $modelManager;
    #[Inject] protected ValidatorInterface $validator;

    public function resolve(ReflectionParameter $parameter, ?string $type, string $name): mixed
    {
        if (!is_subclass_of($type, ModelInterface::class)) {
            return null;
        }

        $primaryKey = $this->modelManager->getprimaryKey($type);
        if ($this->request->has($primaryKey)) {
            $id = $this->request->get($primaryKey);
        } elseif ($this->request->has($name)) {
            $id = $this->request->get($name);
        } else {
            throw new ValidateFailedException([$primaryKey => $this->validator->createError('required', $primaryKey)]);
        }

        if (!is_int($id) && !is_string($id)) {
            throw new BadRequestException('id is invalid.');
        }

        /** @var ModelInterface $type */
        return $type::get($id);
    }
}