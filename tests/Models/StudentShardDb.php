<?php

namespace Tests\Models;

use ManaPHP\Data\Db\Model;
use ManaPHP\Data\Db\Query;

class StudentShardDb extends Model
{
    public $id;
    public $age;
    public $name;

    public function table($context = null)
    {
        return '_student';
    }

    public function db($context = null)
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