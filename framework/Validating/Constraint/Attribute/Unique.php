<?php
declare(strict_types=1);

namespace ManaPHP\Validating\Constraint\Attribute;

use Attribute;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Model\ModelInterface;
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
        /** @var ModelInterface $source */
        $source = $validation->source;

        if (!$source instanceof ModelInterface) {
            throw new MisuseException(sprintf('%s is not a model', $source::class));
        }

        $filters = [$validation->field => $validation->value];
        foreach ($this->filters as $key => $value) {
            if (is_int($key)) {
                $filters[$value] = $source->$value;
            } else {
                $filters[$key] = $value;
            }
        }

        return !$source::exists($filters);
    }
}