<?php
declare(strict_types=1);

namespace ManaPHP\Data\Relation;

use ManaPHP\Data\AbstractRelation;
use ManaPHP\Data\Model\ManagerInterface;
use ManaPHP\Data\ModelInterface;
use ManaPHP\Data\QueryInterface;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Helper\Container;

class HasMany extends AbstractRelation
{
    protected string $thisField;
    protected string $thatField;

    public function __construct(string $thisModel, string $thatModel)
    {
        $modelManager = Container::get(ManagerInterface::class);

        $this->thisModel = $thisModel;
        $this->thisField = $modelManager->getPrimaryKey($thisModel);
        $this->thatModel = $thatModel;
        $this->thatField = $modelManager->getforeignedKey($thisModel);
    }

    public function earlyLoad(array $r, QueryInterface $query, string $name): array
    {
        $thisField = $this->thisField;
        $thatField = $this->thatField;

        $r_index = [];
        foreach ($r as $ri => $rv) {
            $r_index[$rv[$thisField]] = $ri;
        }

        $ids = array_column($r, $thisField);
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
        $thisField = $this->thisField;

        return $thatModel::select()->where([$this->thatField => $instance->$thisField])->setFetchType(true);
    }
}
