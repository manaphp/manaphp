<?php
declare(strict_types=1);

return [
    'default'                                      => 'The :field is invalid.',
    'ManaPHP\Validating\Rule\Attribute\Required'   => 'The :field field is required.',
    'ManaPHP\Validating\Rule\Attribute\Boolean'    => 'The :field field must be true or false.',
    'ManaPHP\Validating\Rule\Attribute\Integer'    => 'The :field must be an integer.',
    'ManaPHP\Validating\Rule\Attribute\Double'     => 'The :field must be a float.',
    'ManaPHP\Validating\Rule\Attribute\Date'       => 'The :field is not a valid date.',
    'ManaPHP\Validating\Rule\Attribute\Range'      => 'The :field must be between :min and :max.',
    'ManaPHP\Validating\Rule\Attribute\Min'        => 'The :field must be not less than :min.',
    'ManaPHP\Validating\Rule\Attribute\Max'        => 'The :field must be not great than :max.',
    'ManaPHP\Validating\Rule\Attribute\MinLength'  => 'The :field must be at least :min characters.',
    'ManaPHP\Validating\Rule\Attribute\MaxLength'  => 'The :field must be shorter than :max characters.',
    'ManaPHP\Validating\Rule\Attribute\Length'     => 'The :field must be :min :max characters.',
    'ManaPHP\Validating\Rule\Attribute\Equal'      => 'The :field must equal :parameter.',
    'ManaPHP\Validating\Rule\Attribute\Alpha'      => 'The :field may only contain letters.',
    'ManaPHP\Validating\Rule\Attribute\Digit'      => 'The :field must be digits.',
    'ManaPHP\Validating\Rule\Attribute\Alnum'      => 'The :field may only contain letters and numbers.',
    'ManaPHP\Validating\Rule\Attribute\Email'      => 'The :field must be a valid email address.',
    'ManaPHP\Validating\Rule\Attribute\Json'       => 'The :field must be a valid JSON string.',
    'ManaPHP\Validating\Rule\Attribute\Ip'         => 'The :field must be a valid IP address.',
    'ManaPHP\Validating\Rule\Attribute\Unique'     => 'The :field must be unique',
    'ManaPHP\Validating\Rule\Attribute\IsReadonly' => 'The :field can not be modified',
    'ManaPHP\Validating\Rule\Attribute\Uuid'       => 'The :field is not a valid uuid'
];