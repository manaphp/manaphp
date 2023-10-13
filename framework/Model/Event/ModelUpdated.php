<?php
declare(strict_types=1);

namespace ManaPHP\Model\Event;

use ManaPHP\Eventing\Attribute\Verbosity;
use ManaPHP\Model\ModelInterface;

#[Verbosity(Verbosity::HIGH)]
class ModelUpdated
{
    public function __construct(public ModelInterface $model)
    {

    }
}