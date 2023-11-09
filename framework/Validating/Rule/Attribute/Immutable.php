<?php
declare(strict_types=1);

namespace ManaPHP\Validating\Rule\Attribute;

use Attribute;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Model\ModelInterface;
use ManaPHP\Validating\AbstractRule;
use ManaPHP\Validating\Validation;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Immutable extends AbstractRule
{
    public function __construct(public ?string $message = null)
    {

    }

    public function validate(Validation $validation): bool
    {
        /** @var ModelInterface $source */
        $source = $validation->source;
        if (!$source instanceof ModelInterface) {
            throw new MisuseException(\sprintf('%s is not a model', $source::class));
        }

        $field = $validation->field;
        $snapshot = $source->getSnapshotData();

        return !isset($snapshot[$field]) || $snapshot[$field] === $validation->value;
    }
}