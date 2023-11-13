<?php
declare(strict_types=1);

namespace App\Models;

use ManaPHP\Validating\Rule\Attribute\Account;
use ManaPHP\Validating\Rule\Attribute\Constant;
use ManaPHP\Validating\Rule\Attribute\Email;
use ManaPHP\Validating\Rule\Attribute\Immutable;
use ManaPHP\Validating\Rule\Attribute\Length;
use ManaPHP\Validating\Rule\Attribute\Lowercase;
use ManaPHP\Validating\Rule\Attribute\Unique;

class User extends Model
{
    const STATUS_INIT = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_LOCKED = 2;

    const PASSWORD_LENGTH = '1-30';

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

    public function create(): static
    {
        $this->salt = bin2hex(random_bytes(8));

        $this->password = $this->hashPassword(input('password', ['string', self::PASSWORD_LENGTH]));

        return parent::create();
    }

    public function update(array $kv = []): static
    {
        foreach ($kv as $key => $val) {
            $this->$key = $val;
        }

        if ($this->hasChanged(['password'])) {
            $this->salt = bin2hex(random_bytes(8));
            $this->password = $this->hashPassword($this->password);
        }

        return parent::update();
    }
}