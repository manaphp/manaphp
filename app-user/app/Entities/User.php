<?php
declare(strict_types=1);

namespace App\Entities;

use ManaPHP\Persistence\Attribute\Id;
use ManaPHP\Persistence\Event\EntityCreating;
use ManaPHP\Persistence\Event\EntityEventInterface;
use ManaPHP\Persistence\Event\EntityUpdating;
use ManaPHP\Validating\Constraint\Attribute\Account;
use ManaPHP\Validating\Constraint\Attribute\Constant;
use ManaPHP\Validating\Constraint\Attribute\Email;
use ManaPHP\Validating\Constraint\Attribute\Immutable;
use ManaPHP\Validating\Constraint\Attribute\Length;
use ManaPHP\Validating\Constraint\Attribute\Lowercase;
use ManaPHP\Validating\Constraint\Attribute\Unique;

class User extends Entity
{
    public const STATUS_INIT = 0;
    public const STATUS_ACTIVE = 1;
    public const STATUS_LOCKED = 2;

    #[Id]
    public $user_id;

    #[Length(4, 16), Account, Immutable]
    public $user_name;

    #[Constant]
    public $status;

    #[Email, Lowercase, Unique]
    public $email;

    public $salt;
    public $password;
    public $login_ip;
    public $login_time;
    public $session_id;
    public $creator_name;
    public $updator_name;
    public $created_time;
    public $updated_time;

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