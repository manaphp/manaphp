<?php
declare(strict_types=1);

namespace ManaPHP\Invoking\ValueResolver;

use ManaPHP\Data\Model\ManagerInterface;
use ManaPHP\Data\ModelInterface;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Exception\BadRequestException;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Invoking\ObjectValueResolverInterface;

class Model implements ObjectValueResolverInterface
{
    #[Inject] protected RequestInterface $request;
    #[Inject] protected ManagerInterface $modelManager;

    public function resolve(?string $type, string $name): mixed
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