<?php
declare(strict_types=1);

namespace ManaPHP\Model\Event;

use ManaPHP\Model\ModelInterface;

class ModelSaving
{
    public function __construct(public ModelInterface $model)
    {

    }
}