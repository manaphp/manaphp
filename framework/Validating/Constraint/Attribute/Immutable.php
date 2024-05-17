<?php
declare(strict_types=1);

namespace ManaPHP\Validating\Constraint\Attribute;

use Attribute;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Persistence\Entity;
use ManaPHP\Validating\AbstractConstraint;
use ManaPHP\Validating\Validation;
use function sprintf;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Immutable extends AbstractConstraint
{
    public function validate(Validation $validation): bool
    {
        /** @var Entity $source */
        $source = $validation->source;
        if (!$source instanceof Entity) {
            throw new MisuseException(sprintf('%s is not a entity', $source::class));
        }

        $field = $validation->field;
        $snapshot = $source->getSnapshotData();

        return !isset($snapshot[$field]) || $snapshot[$field] === $validation->value;
    }
}