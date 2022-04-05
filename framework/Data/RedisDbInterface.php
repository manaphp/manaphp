<?php
declare(strict_types=1);

namespace ManaPHP\Data;

use ManaPHP\Di\Attribute\Primary;

/**
 * @mixin \Redis
 */
#[Primary('ManaPHP\Data\RedisInterface')]
interface RedisDbInterface
{

}