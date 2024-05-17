<?php
declare(strict_types=1);

namespace ManaPHP\Validating\Constraint\Attribute;

use Attribute;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Helper\Container;
use ManaPHP\Persistence\Entity;
use ManaPHP\Persistence\EntityMetadataInterface;
use ManaPHP\Validating\AbstractConstraint;
use ManaPHP\Validating\Validation;
use function is_int;
use function sprintf;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Unique extends AbstractConstraint
{
    public function __construct(public array $filters = [], public ?string $message = null)
    {
        parent::__construct($message);
    }

    public function validate(Validation $validation): bool
    {
        /** @var Entity $source */
        $source = $validation->source;

        if (!$source instanceof Entity) {
            throw new MisuseException(sprintf('%s is not a entity', $source::class));
        }

        $filters = [$validation->field => $validation->value];
        foreach ($this->filters as $key => $value) {
            if (is_int($key)) {
                $filters[$value] = $source->$value;
            } else {
                $filters[$key] = $value;
            }
        }

        $entityMetadata = Container::get(EntityMetadataInterface::class);
        $primaryKey = $entityMetadata->getPrimaryKey($source::class);
        if ($primaryKey !== null && isset($source->$primaryKey)) {
            $filters[$primaryKey . '!='] = $source->$primaryKey;
        }

        return !$entityMetadata->getRepository($source::class)->exists($filters);
    }
}