<?php
declare(strict_types=1);

namespace ManaPHP\Model\Relation;

use ManaPHP\Helper\Container;
use ManaPHP\Model\AbstractRelation;
use ManaPHP\Model\ModelInterface;
use ManaPHP\Model\ModelManagerInterface;
use ManaPHP\Query\QueryInterface;

class BelongsTo extends AbstractRelation
{
    protected string $selfField;
    protected string $thatField;

    public function __construct(string|array $self, string $thatModel)
    {
        $modelManager = Container::get(ModelManagerInterface::class);

        if (is_string($self)) {
            $this->selfModel = $self;
            $this->selfField = $modelManager->getReferencedKey($thatModel);
        } else {
            list($this->selfModel, $this->selfField) = $self;
        }

        $this->thatModel = $thatModel;
        $this->thatField = $modelManager->getPrimaryKey($thatModel);
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
        /** @var ModelInterface $thatModel */
        $thatModel = $this->thatModel;
        $selfField = $this->selfField;
        $thatField = $this->thatField;

        return $thatModel::select()->where([$thatField => $instance->$selfField])->setFetchType(false);
    }
}
