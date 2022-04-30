<?php
declare(strict_types=1);

namespace ManaPHP\Data\Merger;

use ManaPHP\Component;

/**
 * @property-read \ManaPHP\Data\Model\ManagerInterface $modelManager
 */
class FieldsInferer extends Component implements FieldsInfererInterface
{
    protected array $fields = [];

    public function fields(AbstractModel $model): array
    {
        $class = $model::class;
        if (($fields = $this->fields[$class] ?? null) === null) {
            $fields = [];
            foreach (get_class_vars($class) as $field => $value) {
                if ($value === null && $field[0] !== '_') {
                    $fields[] = $field;
                }
            }
            $model2 = $model->getModel();
            return $this->fields[$class] = $fields ?: $this->modelManager->getFields($model2::class);
        } else {
            return $fields;
        }
    }
}