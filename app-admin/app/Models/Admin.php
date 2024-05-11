<?php
declare(strict_types=1);

namespace App\Models;

use App\Areas\Rbac\Models\AdminRole;
use App\Areas\Rbac\Models\Role;
use App\Repositories\AdminRepository;
use ManaPHP\Identifying\IdentityInterface;
use ManaPHP\Invoking\ArgumentResolvable;
use ManaPHP\Model\Event\ModelCreating;
use ManaPHP\Model\Event\ModelUpdating;
use ManaPHP\Model\Relation\HasManyToMany;
use ManaPHP\Validating\Constraint\Attribute\Account;
use ManaPHP\Validating\Constraint\Attribute\Constant;
use ManaPHP\Validating\Constraint\Attribute\Defaults;
use ManaPHP\Validating\Constraint\Attribute\Email;
use ManaPHP\Validating\Constraint\Attribute\Immutable;
use ManaPHP\Validating\Constraint\Attribute\Length;
use ManaPHP\Validating\Constraint\Attribute\MaxLength;
use ManaPHP\Validating\Constraint\Attribute\Unique;
use Psr\Container\ContainerInterface;

class Admin extends Model implements ArgumentResolvable
{
    public const STATUS_INIT = 0;
    public const STATUS_ACTIVE = 1;
    public const STATUS_LOCKED = 2;

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

    public static function argumentResolve(ContainerInterface $container): mixed
    {
        $identity = $container->get(IdentityInterface::class);
        return $container->get(AdminRepository::class)->get($identity->getId());
    }

    public function relations(): array
    {
        return ['roles' => new HasManyToMany(static::class, Role::class, AdminRole::class)];
    }

    public function hashPassword(string $password): string
    {
        return md5(md5($password) . $this->salt);
    }

    public function verifyPassword(string $password): bool
    {
        return $this->hashPassword($password) === $this->password;
    }

    public function fireEvent(object $event): void
    {
        parent::fireEvent($event);

        if ($event instanceof ModelCreating || ($event instanceof ModelUpdating && $this->hasChanged(['password']))) {
            $this->salt = bin2hex(random_bytes(8));
            $this->password = $this->hashPassword($this->password);
        }
    }
}