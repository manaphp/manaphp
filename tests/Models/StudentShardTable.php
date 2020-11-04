<?php

namespace Tests\Models;

use ManaPHP\Db\Model;
use ManaPHP\Db\Query;

class StudentShardTable extends Model
{
    public $id;
    public $age;
    public $name;

    public function getTable($context = null)
    {
        if ($context === true) {
            return '_student';
        }

        if ($context instanceof StudentShardTable) {
            $student_id = $context->id;
        } elseif (is_array($context)) {
            if (isset($context['id'])) {
                $student_id = $context['id'];
            }
        } elseif ($context instanceof Query) {
            $student_id = $context->getBind('id');
        }

        if (isset($student_id)) {
            return 'student_' . ($student_id % 64);
        } else {
            return false;
        }
    }

}