<?php
declare(strict_types=1);

namespace ManaPHP\Data\Relation;

use ManaPHP\Data\AbstractRelation;
use ManaPHP\Data\Model\ManagerInterface;
use ManaPHP\Data\ModelInterface;
use ManaPHP\Data\QueryInterface;
use ManaPHP\Helper\Container;

class HasOne extends AbstractRelation
{
    protected string $thisField;
    protected string $thatField;

    public function __construct(string $thisModel, string $thatModel)
    {
        $modelManager = Container::get(ManagerInterface::class);

        $this->thisModel = $thisModel;
        $this->thisField = $modelManager->getPrimaryKey($thisModel);
        $this->thatModel = $thatModel;
        $this->thatField = $modelManager->getForeignedKey($thisModel);
    }

    public function earlyLoad(array $r, QueryInterface $query, string $name): array
    {
        $thisField = $this->thisField;
        $thatField = $this->thatField;

        $ids = array_values(array_unique(array_column($r, $thisField)));
        $data = $query->whereIn($thatField, $ids)->indexBy($thatField)->fetch();

        foreach ($r as $ri => $rv) {
            $key = $rv[$thisField];
            $r[$ri][$name] = $data[$key] ?? null;
        }

        return $r;
    }

    public function lazyLoad(ModelInterface $instance): QueryInterface
    {
        /** @var \ManaPHP\Data\ModelInterface $thatModel */
        $thatModel = $this->thatModel;
        $thisField = $this->thisField;
        $thatField = $this->thatField;

        return $thatModel::select()->whereEq($thatField, $instance->$thisField)->setFetchType(false);
    }
}
