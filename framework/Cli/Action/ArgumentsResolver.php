<?php
declare(strict_types=1);

namespace ManaPHP\Cli\Action;

use ManaPHP\Di\Attribute\Value;

class ArgumentsResolver extends \ManaPHP\Invoking\ArgumentsResolver implements ArgumentsResolverInterface
{
    #[Value] protected array $resolvers = ['options'];
}