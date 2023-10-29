<?php
declare(strict_types=1);

namespace ManaPHP\Eventing;

use JsonSerializable;
use Stringable;

class EventWrapper implements JsonSerializable, Stringable
{
    public function __construct(
        public object $event,
        public ?string $fields = null,
    ) {

    }

    public function jsonSerialize(): array
    {
        $event = $this->event;

        if ($event instanceof JsonSerializable) {
            $data = $event->jsonSerialize();
        } else {
            $data = [];

            if ($this->fields !== null) {
                $fields = preg_split('#[\s,]+#', $this->fields, -1, PREG_SPLIT_NO_EMPTY);
                foreach ($fields as $field) {
                    $data[$field] = $event->$field;
                }
            } else {
                foreach (get_object_vars($event) as $key => $val) {
                    if (\is_object($val)) {
                        continue;
                    }

                    $data[$key] = $val;
                }
            }
        }

        return $data;
    }

    public function __toString(): string
    {
        return json_stringify($this->jsonSerialize());
    }
}