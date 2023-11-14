<?php
declare(strict_types=1);

return [
    'default'                                     => 'The :field is invalid.',
    'ManaPHP\Validating\Constraint\Attribute\Type'      => 'The :field data type is not :type.',
    'ManaPHP\Validating\Constraint\Attribute\Required'  => 'The :field field is required.',
    'ManaPHP\Validating\Constraint\Attribute\Date'      => 'The :field is not a valid date.',
    'ManaPHP\Validating\Constraint\Attribute\Range'     => 'The :field must be between :min and :max.',
    'ManaPHP\Validating\Constraint\Attribute\Min'       => 'The :field must be not less than :min.',
    'ManaPHP\Validating\Constraint\Attribute\Max'       => 'The :field must be not great than :max.',
    'ManaPHP\Validating\Constraint\Attribute\MinLength' => 'The :field must be at least :min characters.',
    'ManaPHP\Validating\Constraint\Attribute\MaxLength' => 'The :field must be shorter than :max characters.',
    'ManaPHP\Validating\Constraint\Attribute\Length'    => 'The :field must be :min :max characters.',
    'ManaPHP\Validating\Constraint\Attribute\Alpha'     => 'The :field may only contain letters.',
    'ManaPHP\Validating\Constraint\Attribute\Digit'     => 'The :field must be digits.',
    'ManaPHP\Validating\Constraint\Attribute\Alnum'     => 'The :field may only contain letters and numbers.',
    'ManaPHP\Validating\Constraint\Attribute\Email'     => 'The :field must be a valid email address.',
    'ManaPHP\Validating\Constraint\Attribute\Json'      => 'The :field must be a valid JSON string.',
    'ManaPHP\Validating\Constraint\Attribute\Ip'        => 'The :field must be a valid IP address.',
    'ManaPHP\Validating\Constraint\Attribute\Unique'    => 'The :field must be unique',
    'ManaPHP\Validating\Constraint\Attribute\Immutable' => 'The :field can not be modified',
    'ManaPHP\Validating\Constraint\Attribute\Uuid'      => 'The :field is not a valid uuid'
];