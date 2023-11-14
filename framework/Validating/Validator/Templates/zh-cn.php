<?php
declare(strict_types=1);

return [
    'default'                                           => ':field 值无效',
    'ManaPHP\Validating\Constraint\Attribute\Type'      => ':field 数据类型不正确',
    'ManaPHP\Validating\Constraint\Attribute\Required'  => ':field 是必填项',
    'ManaPHP\Validating\Constraint\Attribute\Date'      => ':field 日期格式错误',
    'ManaPHP\Validating\Constraint\Attribute\Range'     => ':field 有效范围为: :parameter',
    'ManaPHP\Validating\Constraint\Attribute\Min'       => ':field 不能小于 :min',
    'ManaPHP\Validating\Constraint\Attribute\Max'       => ':field 不能大于 :max',
    'ManaPHP\Validating\Constraint\Attribute\MinLength' => ':field 最少 :min 个字符',
    'ManaPHP\Validating\Constraint\Attribute\MaxLength' => ':field 最多 :max 个字符',
    'ManaPHP\Validating\Constraint\Attribute\Length'    => ':field 长度有效范围为: :length',
    'ManaPHP\Validating\Constraint\Attribute\Alpha'     => ':field 包只包含字母',
    'ManaPHP\Validating\Constraint\Attribute\Digit'     => ':field 只能包含数字',
    'ManaPHP\Validating\Constraint\Attribute\Alnum'     => ':field 只能包含字母或数字',
    'ManaPHP\Validating\Constraint\Attribute\Email'     => ':field 邮件格式错误',
    'ManaPHP\Validating\Constraint\Attribute\Json'      => ':field 不是有效的JSON串',
    'ManaPHP\Validating\Constraint\Attribute\Ip'        => ':field IP地址格式无效',
    'ManaPHP\Validating\Constraint\Attribute\Unique'    => ' :field 不能重复',
    'ManaPHP\Validating\Constraint\Attribute\Immutable' => ' 不能修改 :field 字段的值',
    'ManaPHP\Validating\Constraint\Attribute\Uuid'      => ':field 不是有效的uuid',
];