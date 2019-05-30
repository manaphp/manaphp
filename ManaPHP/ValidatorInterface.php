<?php
namespace ManaPHP;

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
     * @param string                $field
     * @param \ManaPHP\Model|mixed  $model
     * @param array|string|\Closure $rules
     *
     * @return mixed
     */
    public function validate($field, $model, $rules);

    /**
     * @param string                $field
     * @param \ManaPHP\Model        $model
     * @param array|string|\Closure $rules
     *
     * @return mixed
     * @throws \ManaPHP\Validator\ValidateFailedException
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