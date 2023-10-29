<?php
declare(strict_types=1);

namespace ManaPHP\Db;

class PreparedEmulator implements PreparedEmulatorInterface
{
    protected function quote(string $value): string
    {
        return "'" . str_replace("'", "\\'", $value) . "'";
    }

    protected function parseBindValue(mixed $value, int $preservedStrLength): int|string
    {
        if (\is_string($value)) {
            $quoted = $this->quote($value);
            if ($preservedStrLength > 0 && \strlen($quoted) >= $preservedStrLength) {
                return substr($quoted, 0, $preservedStrLength) . '...';
            } else {
                return $quoted;
            }
        } elseif (\is_int($value)) {
            return $value;
        } elseif ($value === null) {
            return 'NULL';
        } elseif (\is_bool($value)) {
            return (int)$value;
        } else {
            return $value;
        }
    }

    public function emulate(string $sql, array $bind, int $preservedStrLength = -1): string
    {
        if ($bind === [] || isset($bind[0])) {
            return $sql;
        }

        $replaces = [];
        foreach ($bind as $key => $value) {
            $replaces[':' . $key] = $this->parseBindValue($value, $preservedStrLength);
        }

        return strtr($sql, $replaces);
    }
}