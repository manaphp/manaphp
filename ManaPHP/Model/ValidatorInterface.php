<?php
namespace ManaPHP\Model;

interface ValidatorInterface
{
    /**
     * @param \ManaPHP\Model $model
     * @param array|string   $fields
     *
     * @return  void
     */
    public function validate($model, $fields = []);

    /**
     * @param \ManaPHP\Model\Validator\Message $message
     *
     * @return static
     */
    public function appendMessage($message);

    /**
     * @param string $field
     *
     * @return \ManaPHP\Model\Validator\Message[]
     */
    public function getMessages($field = null);
}