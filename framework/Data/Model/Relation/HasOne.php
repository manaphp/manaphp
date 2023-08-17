<?php
declare(strict_types=1);

namespace ManaPHP\Data\Model\Relation;

use ManaPHP\Data\Model\AbstractRelation;
use ManaPHP\Data\ModelInterface;
use ManaPHP\Data\ModelManagerInterface;
use ManaPHP\Data\QueryInterface;
use ManaPHP\Helper\Container;

class HasOne extends AbstractRelation
{
    protected string $selfField;
    protected string $thatField;

    public function __construct(string $selfModel, string|array $that)
    {
        $modelManager = Container::get(ModelManagerInterface::class);

        $this->selfModel = $selfModel;
        $this->selfField = $modelManager->getPrimaryKey($selfModel);

        if (is_string($that)) {
            $this->thatModel = $that;
            $this->thatField = $modelManager->getReferencedKey($selfModel);
        } else {
            list($this->thatModel, $this->thatField) = $that;
        }
    }

    public function earlyLoad(array $r, QueryInterface $query, string $name): array
    {
        $selfField = $this->selfField;
        $thatField = $this->thatField;

        $ids = array_values(array_unique(array_column($r, $selfField)));
        $data = $query->whereIn($thatField, $ids)->indexBy($thatField)->fetch();

        foreach ($r as $ri => $rv) {
            $key = $rv[$selfField];
            $r[$ri][$name] = $data[$key] ?? null;
        }

        return $r;
    }

    public function lazyLoad(ModelInterface $instance): QueryInterface
    {
        /** @var \ManaPHP\Data\ModelInterface $thatModel */
        $thatModel = $this->thatModel;
        $selfField = $this->selfField;
        $thatField = $this->thatField;

        return $thatModel::select()->where([$thatField => $instance->$selfField])->setFetchType(false);
    }
}
