<?php
declare(strict_types=1);

namespace App\Entities;

use App\Areas\Rbac\Entities\AdminRole;
use App\Repositories\AdminRepository;
use ManaPHP\Identifying\IdentityInterface;
use ManaPHP\Invoking\ArgumentResolvable;
use ManaPHP\Persistence\Attribute\HasManyToMany;
use ManaPHP\Persistence\Attribute\Id;
use ManaPHP\Persistence\Event\EntityCreating;
use ManaPHP\Persistence\Event\EntityEventInterface;
use ManaPHP\Persistence\Event\EntityUpdating;
use ManaPHP\Validating\Constraint\Attribute\Account;
use ManaPHP\Validating\Constraint\Attribute\Constant;
use ManaPHP\Validating\Constraint\Attribute\Defaults;
use ManaPHP\Validating\Constraint\Attribute\Email;
use ManaPHP\Validating\Constraint\Attribute\Immutable;
use ManaPHP\Validating\Constraint\Attribute\Length;
use ManaPHP\Validating\Constraint\Attribute\MaxLength;
use ManaPHP\Validating\Constraint\Attribute\Unique;
use Psr\Container\ContainerInterface;

class Admin extends Entity implements ArgumentResolvable
{
    public const STATUS_INIT = 0;
    public const STATUS_ACTIVE = 1;
    public const STATUS_LOCKED = 2;

    #[Id]
    public int $admin_id;

    #[Length(4, 16), Account, Immutable, Unique]
    public string $admin_name;

    #[Constant]
    public int $status;

    public int $type;
    public int $tag;

    #[Email, Unique]
    public string $email;

    public string $salt;

    #[Length(6, 16)]
    public string $password;

    #[Defaults(''), MaxLength(64)]
    public string $white_ip;

    public string $login_ip;
    public int $login_time;
    public string $session_id;
    public string $creator_name;
    public string $updator_name;
    public int $created_time;
    public int $updated_time;

    /** @var array<Role> */
    #[HasManyToMany(AdminRole::class, orderBy: ['role_id' => SORT_ASC])]
    public array $roles;

    public static function argumentResolve(ContainerInterface $container): mixed
    {
        $identity = $container->get(IdentityInterface::class);
        return $container->get(AdminRepository::class)->get($identity->getId());
    }

    public function hashPassword(string $password): string
    {
        return md5(md5($password) . $this->salt);
    }

    public function verifyPassword(string $password): bool
    {
        return $this->hashPassword($password) === $this->password;
    }

    public function onEvent(EntityEventInterface $entityEvent): void
    {
        if ($entityEvent instanceof EntityCreating
            || ($entityEvent instanceof EntityUpdating
                && $entityEvent->hasChanged(['password']))
        ) {
            $this->salt = bin2hex(random_bytes(8));
            $this->password = $this->hashPassword($this->password);
        }
    }
}