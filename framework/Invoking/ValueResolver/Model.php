<?php
declare(strict_types=1);

namespace ManaPHP\Invoking\ValueResolver;

use ManaPHP\Component;
use ManaPHP\Data\ModelInterface;
use ManaPHP\Exception\BadRequestException;
use ManaPHP\Invoking\ObjectValueResolverInterface;

/**
 * @property-read \ManaPHP\Http\RequestInterface       $request
 * @property-read \ManaPHP\Data\Model\ManagerInterface $modelManager
 */
class Model extends Component implements ObjectValueResolverInterface
{
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