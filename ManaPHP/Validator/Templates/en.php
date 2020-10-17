<?php

return [
    'default' => 'The :field is invalid.',
    'required' => 'The :field field is required.',
    'bool' => 'The :field field must be true or false.',
    'int' => 'The :field must be an integer.',
    'float' => 'The :field must be a float.',
    'date' => 'The :field is not a valid date.',
    'range' => static function ($field, $parameter) {
        $tr = [':field' => $field];
        $pos = strpos($parameter, '-', 1);
        $tr[':min'] = substr($parameter, 0, $pos);
        $tr[':max'] = substr($parameter, $pos + 1);
        return strtr('The :field must be between :min and :max.', $tr);
    },
    'min' => 'The :field must be not less than :parameter.',
    'max' => 'The :field must be not great than :parameter.',
    'minLength' => 'The :field must be at least :parameter characters.',
    'maxLength' => 'The :field must be shorter than :parameter characters.',
    'length' => 'The :field must be :parameter characters.',
    'equal' => 'The :field must equal :parameter.',
    'alpha' => 'The :field may only contain letters.',
    'digit' => 'The :field must be digits.',
    'alnum' => 'The :field may only contain letters and numbers.',
    'email' => 'The :field must be a valid email address.',
    'json' => 'The :field must be a valid JSON string.',
    'ip' => 'The :field must be a valid IP address.',
    'unique' => 'The :field must be unique',
    'readonly' => 'The :field can not be modified',
];