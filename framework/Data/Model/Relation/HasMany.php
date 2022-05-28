<?php
declare(strict_types=1);

namespace ManaPHP\Data\Model\Relation;

use ManaPHP\Data\Model\AbstractRelation;
use ManaPHP\Data\Model\ManagerInterface;
use ManaPHP\Data\ModelInterface;
use ManaPHP\Data\QueryInterface;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Helper\Container;

class HasMany extends AbstractRelation
{
    protected string $selfField;
    protected string $thatField;

    public function __construct(string $selfModel, string|array $that)
    {
        $modelManager = Container::get(ManagerInterface::class);

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

        $r_index = [];
        foreach ($r as $ri => $rv) {
            $r_index[$rv[$selfField]] = $ri;
        }

        $ids = array_column($r, $selfField);
        $data = $query->whereIn($thatField, $ids)->fetch();

        if (isset($data[0]) && !isset($data[0][$thatField])) {
            throw new MisuseException(['missing `%s` field in `%s` with', $thatField, $name]);
        }

        $rd = [];
        foreach ($data as $dv) {
            $rd[$r_index[$dv[$thatField]]][] = $dv;
        }

        foreach ($r as $ri => $rv) {
            $r[$ri][$name] = $rd[$ri] ?? [];
        }

        return $r;
    }

    public function lazyLoad(ModelInterface $instance): QueryInterface
    {
        /** @var \ManaPHP\Data\ModelInterface $thatModel */
        $thatModel = $this->thatModel;
        $selfField = $this->selfField;

        return $thatModel::select()->where([$this->thatField => $instance->$selfField])->setFetchType(true);
    }
}
