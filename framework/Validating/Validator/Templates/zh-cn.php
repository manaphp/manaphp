<?php
declare(strict_types=1);

return [
    'default'                                     => ':field 值无效',
    'ManaPHP\Validating\Rule\Attribute\Type'      => ':field 数据类型不正确',
    'ManaPHP\Validating\Rule\Attribute\Required'  => ':field 是必填项',
    'ManaPHP\Validating\Rule\Attribute\Date'      => ':field 日期格式错误',
    'ManaPHP\Validating\Rule\Attribute\Range'     => ':field 有效范围为: :parameter',
    'ManaPHP\Validating\Rule\Attribute\Min'       => ':field 不能小于 :min',
    'ManaPHP\Validating\Rule\Attribute\Max'       => ':field 不能大于 :max',
    'ManaPHP\Validating\Rule\Attribute\MinLength' => ':field 最少 :min 个字符',
    'ManaPHP\Validating\Rule\Attribute\MaxLength' => ':field 最多 :max 个字符',
    'ManaPHP\Validating\Rule\Attribute\Length'    => ':field 长度有效范围为: :length',
    'ManaPHP\Validating\Rule\Attribute\Alpha'     => ':field 包只包含字母',
    'ManaPHP\Validating\Rule\Attribute\Digit'     => ':field 只能包含数字',
    'ManaPHP\Validating\Rule\Attribute\Alnum'     => ':field 只能包含字母或数字',
    'ManaPHP\Validating\Rule\Attribute\Email'     => ':field 邮件格式错误',
    'ManaPHP\Validating\Rule\Attribute\Json'      => ':field 不是有效的JSON串',
    'ManaPHP\Validating\Rule\Attribute\Ip'        => ':field IP地址格式无效',
    'ManaPHP\Validating\Rule\Attribute\Unique'    => ' :field 不能重复',
    'ManaPHP\Validating\Rule\Attribute\Immutable' => ' 不能修改 :field 字段的值',
    'ManaPHP\Validating\Rule\Attribute\Uuid'      => ':field 不是有效的uuid',
];