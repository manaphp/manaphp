<?php
declare(strict_types=1);

namespace App\Models;

use ManaPHP\Identifying\IdentityInterface;
use ManaPHP\Invoking\ArgumentResolvable;
use ManaPHP\Model\Event\ModelCreating;
use ManaPHP\Model\Event\ModelUpdating;
use ManaPHP\Validating\Constraint\Attribute\Account;
use ManaPHP\Validating\Constraint\Attribute\Constant;
use ManaPHP\Validating\Constraint\Attribute\Email;
use ManaPHP\Validating\Constraint\Attribute\Immutable;
use ManaPHP\Validating\Constraint\Attribute\Length;
use ManaPHP\Validating\Constraint\Attribute\Unique;
use Psr\Container\ContainerInterface;

class User extends Model implements ArgumentResolvable
{
    public const STATUS_INIT = 0;
    public const STATUS_ACTIVE = 1;
    public const STATUS_LOCKED = 2;

    public int $user_id;
    #[Length(4, 16), Account, Immutable, Unique]
    public string $user_name;
    #[Constant]
    public int $status;
    #[Email, Unique]
    public string $email;
    public string $salt;
    #[Length(6, 16)]
    public string $password;
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
        return static::get($identity->getId());
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