<?php
declare(strict_types=1);

namespace ManaPHP\Http\Server\Event;

use ManaPHP\Eventing\Attribute\Verbosity;

#[Verbosity(Verbosity::HIGH)]
class RequestAuthorizing extends RequestDispatchBase
{

}