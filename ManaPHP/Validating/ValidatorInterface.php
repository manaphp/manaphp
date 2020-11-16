<?php

namespace ManaPHP\Validating;

interface ValidatorInterface
{
    /**
     * @return string
     */
    public function getLocale();

    /**
     * @param string $locale
     *
     * @return static
     */
    public function setLocale($locale);

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