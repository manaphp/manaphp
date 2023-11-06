<?php
declare(strict_types=1);

namespace ManaPHP\Http\Action;

use ManaPHP\Di\Attribute\Autowired;

class ArgumentsResolver extends \ManaPHP\Invoking\ArgumentsResolver implements ArgumentsResolverInterface
{
    #[Autowired] protected array $resolvers = ['model', 'request', 'form'];
}