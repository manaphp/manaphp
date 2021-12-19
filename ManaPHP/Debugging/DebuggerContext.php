<?php
declare(strict_types=1);

namespace ManaPHP\Debugging;

class DebuggerContext
{
    public bool $enabled;
    public string $key;
    public array $view = [];
    public array $log = [];
    public array $sql_prepared = [];
    public array $sql_executed = [];
    public int $sql_count = 0;
    public array $mongodb = [];
    public array $events = [];
}