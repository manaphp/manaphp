<?php
declare(strict_types=1);

namespace ManaPHP\Invoking\ValueResolver;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Exception\BadRequestException;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Invoking\ObjectValueResolverInterface;
use ManaPHP\Model\ModelInterface;
use ManaPHP\Model\ModelsInterface;
use ManaPHP\Validating\Constraint\Attribute\Required;
use ManaPHP\Validating\ValidatorInterface;
use ReflectionParameter;
use function is_int;
use function is_string;

class Model implements ObjectValueResolverInterface
{
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected ModelsInterface $models;
    #[Autowired] protected ValidatorInterface $validator;

    public function resolve(ReflectionParameter $parameter, ?string $type, string $name): ?ModelInterface
    {
        if (!is_subclass_of($type, ModelInterface::class)) {
            return null;
        }

        $primaryKey = $this->models->getPrimaryKey($type);
        if (($id = $this->request->input($primaryKey) ?? $this->request->input($name)) === null) {
            $this->validator->validateValue($primaryKey, null, [new Required()]);
        }

        if (!is_int($id) && !is_string($id)) {
            throw new BadRequestException('id is invalid.');
        }

        /** @var ModelInterface $type */
        return $type::get($id);
    }
}