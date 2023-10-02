<?php
declare(strict_types=1);

namespace ManaPHP\Model;

interface AutoFillerInterface
{
    public function fillCreated(ModelInterface $model): void;

    public function fillUpdated(ModelInterface $model): void;
}