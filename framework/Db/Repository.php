<?php
declare(strict_types=1);

namespace ManaPHP\Db;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Persistence\AbstractRepository;

class Repository extends AbstractRepository
{
    #[Autowired] protected EntityManagerInterface $entityManager;

    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }
}