<?php
declare(strict_types=1);

namespace ManaPHP\Model\Event;

use ManaPHP\Model\ModelInterface;

class ModelDeleting
{
    public function __construct(public ModelInterface $model)
    {

    }
}