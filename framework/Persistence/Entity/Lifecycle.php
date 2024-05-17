<?php
declare(strict_types=1);

namespace ManaPHP\Persistence\Entity;

enum Lifecycle
{
    case Creating;
    case Created;
    case Updating;
    case Updated;
    case Deleting;
    case Deleted;
}