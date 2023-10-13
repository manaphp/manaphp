<?php
declare(strict_types=1);

namespace ManaPHP\Db\Event;

use ManaPHP\Eventing\Attribute\Verbosity;

#[Verbosity(Verbosity::HIGH)]
class DbInserted extends DbExecutedBase
{

}