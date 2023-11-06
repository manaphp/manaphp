<?php
declare(strict_types=1);

namespace ManaPHP\Validating\Rule\Attribute;

use Attribute;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Model\ModelInterface;
use ManaPHP\Validating\AbstractRule;
use ManaPHP\Validating\Validation;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Unique extends AbstractRule
{
    public function __construct(public array $filters = [], public ?string $message = null)
    {

    }

    public function validate(Validation $validation): bool
    {
        /** @var ModelInterface $source */
        $source = $validation->source;

        if (!$source instanceof ModelInterface) {
            throw new MisuseException(\sprintf('%s is not a model', $source::class));
        }

        $filters = [$validation->field => $validation->value];
        foreach ($filters as $key => $value) {
            if (\is_int($key)) {
                $filters[$value] = $source->$value;
            } else {
                $filters[$key] = $value;
            }
        }

        return !$source::exists($filters);
    }
}