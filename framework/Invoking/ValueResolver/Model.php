<?php
declare(strict_types=1);

namespace ManaPHP\Invoking\ValueResolver;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Exception\BadRequestException;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Invoking\ObjectValueResolverInterface;
use ManaPHP\Model\ModelInterface;
use ManaPHP\Model\ModelManagerInterface;
use ReflectionParameter;

class Model implements ObjectValueResolverInterface
{
    #[Inject] protected RequestInterface $request;
    #[Inject] protected ModelManagerInterface $modelManager;

    public function resolve(ReflectionParameter $parameter, ?string $type, string $name): mixed
    {
        if (!is_subclass_of($type, ModelInterface::class)) {
            return null;
        }

        /** @var ModelInterface $instance */

        if (($id = $this->request->get($this->modelManager->getprimaryKey($type), '')) !== '') {
            if (!is_int($id) && !is_string($id)) {
                throw new BadRequestException('id is invalid.');
            }
            /** @var ModelInterface $type */
            $instance = $type::get($id);
        } else {
            $instance = new $type;
        }

        $instance->load();

        return $instance;
    }
}