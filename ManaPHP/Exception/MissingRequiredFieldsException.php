<?php
namespace ManaPHP\Exception;

class MissingRequiredFieldsException extends BadRequestException
{
    /**
     * @var string
     */
    protected $_fields;

    /**
     * MissingRequiredFieldException constructor.
     *
     * @param string|array $fields
     * @param \Exception   $previous
     */
    public function __construct($fields, $previous = null)
    {
        if (is_string($fields)) {
            $fields = [$fields];
        }
        $this->_fields = $fields;

        parent::__construct(['missing required fields: :fields', 'fields' => implode(',', $fields)], 0, $previous);
    }

    /**
     * @return string
     */
    public function getFields()
    {
        return $this->_fields;
    }

    public function getJson()
    {
        return ['code' => 400, 'message' => $this->getMessage(), 'fields' => $this->_fields];
    }
}