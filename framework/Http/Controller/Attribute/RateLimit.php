<?php
declare(strict_types=1);

namespace ManaPHP\Http\Controller\Attribute;

use Attribute;
use function is_string;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class RateLimit
{
    public array $limits;
    public ?string $burst;

    public function __construct(string|array $limits, ?string $burst = null)
    {
        $this->limits = is_string($limits) ? [$limits] : $limits;
        $this->burst = $burst;
    }
}