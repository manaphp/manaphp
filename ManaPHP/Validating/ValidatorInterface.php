<?php

namespace ManaPHP\Validating;

interface ValidatorInterface
{
    /**
     * @param string $validate
     * @param string $field
     * @param mixed  $parameter
     *
     * @return string
     */
    public function createError($validate, $field, $parameter = null);

    /**
     * @param string                             $field
     * @param \ManaPHP\Data\ModelInterface|mixed $value
     * @param array|string|\Closure              $rules
     *
     * @return mixed
     */
    public function validate($field, $value, $rules);

    /**
     * @param string                       $field
     * @param \ManaPHP\Data\ModelInterface $model
     * @param array|string|\Closure        $rules
     *
     * @return mixed
     * @throws \ManaPHP\Validating\Validator\ValidateFailedException
     */
    public function validateModel($field, $model, $rules);

    /**
     * @param string                $field
     * @param mixed                 $value
     * @param array|string|\Closure $rules
     *
     * @return mixed
     */
    public function validateValue($field, $value, $rules);
}