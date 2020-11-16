<?php

namespace Tests\Mongodb\Models;

use ManaPHP\Data\Mongodb\Model;

class DataType extends Model
{
    public function getFieldTypes()
    {
        return [
            'v_string'   => 'string',
            'v_int'      => 'int',
            'v_long'     => 'long',
            'v_float'    => 'float',
            'v_double'   => 'double',
            'v_objectid' => 'objectid',
            'v_bool'     => 'bool',
            'v_date'     => 'date'
        ];
    }
}