<?php
namespace ManaPHP\Mvc\View;

interface FormInterface
{
    /**
     * Returns the form name that this model class should use.
     *
     * The form name is mainly used to determine how to name
     * the input fields for the attributes in a model. If the form name is "A" and an attribute
     * name is "b", then the corresponding input name would be "A[b]". If the form name is
     * an empty string, then the input name would be "b".
     *
     * The purpose of the above naming schema is that for forms which contain multiple different models,
     * the attributes of each model are grouped in sub-arrays of the POST-data and it is easier to
     * differentiate between them.
     *
     * @return string the form name of this model class.
     */
    public function getFormName();

    /**
     * @param string $formName
     *
     * @return string
     */
    public function setFormName($formName);

    /**
     * @param bool $submitted
     */
    public function setSubmitted($submitted);

    /**
     * @return bool
     */
    public function isSubmitted();

    /**
     * @return array
     */
    public function getFields();

    /**
     * @return array
     */
    public function toArray();

    /**
     * @param string $field
     * @param string $value
     */
    public function validateField($field, $value);

    /**
     * @param array $data
     */
    public function loadForAction($data = null);

    /**
     * load data for view
     */
    public function loadForView();
}