<?php

namespace Tests\Models;

use ManaPHP\Db\Query;
use ManaPHP\Db\Model;

class StudentShardDb extends Model
{
    public $id;
    public $age;
    public $name;

    public function getTable($context = null)
    {
        return '_student';
    }

    public function getDb($context = null)
    {
        if ($context === true) {
            return 'db';
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