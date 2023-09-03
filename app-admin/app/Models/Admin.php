<?php
declare(strict_types=1);

namespace App\Models;

use App\Areas\Rbac\Models\AdminRole;
use App\Areas\Rbac\Models\Role;
use ManaPHP\Model\Relation\HasManyToMany;

class Admin extends Model
{
    const STATUS_INIT = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_LOCKED = 2;

    const PASSWORD_LENGTH = '1-30';

    public int $admin_id;
    public string $admin_name;
    public int $status;
    public int $type;
    public int $tag;
    public string $email;
    public string $salt;
    public string $password;
    public string $white_ip;
    public string $login_ip;
    public int $login_time;
    public string $session_id;
    public string $creator_name;
    public string $updator_name;
    public int $created_time;
    public int $updated_time;

    public function rules(): array
    {
        return [
            'admin_name' => ['length' => '4-16', 'account', 'readonly'],
            'email'      => ['lower', 'email', 'unique'],
            'status'     => 'const',
            'white_ip'   => ['default' => '', 'maxLength' => 64]
        ];
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

    public function create(array $kv = []): static
    {
        foreach ($kv as $key => $val) {
            $this->$key = $val;
        }

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