<?php

namespace Tests\Models;

use ManaPHP\Data\Db\Model;
use ManaPHP\Data\Db\Query;
use ManaPHP\Data\Model\Attribute\Table;

#[Table('_student')]
class StudentShardDb extends Model
{
    public $id;
    public $age;
    public $name;

    public function connection($context = null): string
    {
        if ($context === true) {
            return 'default';
        }

        if ($context instanceof StudentShardDb) {
            $student_id = $context->id;
        } elseif (is_array($context)) {
            if (isset($context['id'])) {
                $student_id = $context['id'];
            }
        } elseif ($context instanceof Query) {
            $student_id = $context->getBind('id');
        }

        if (isset($student_id)) {
            return 'db_' . ($student_id % 64);
        } else {
            return false;
        }
    }
}