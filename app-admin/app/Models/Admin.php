<?php
declare(strict_types=1);

namespace App\Models;

use App\Areas\Rbac\Models\AdminRole;
use App\Areas\Rbac\Models\Role;
use ManaPHP\Helper\Container;
use ManaPHP\Http\InputInterface;
use ManaPHP\Model\Relation\HasManyToMany;
use ManaPHP\Validating\Rule\Attribute\Account;
use ManaPHP\Validating\Rule\Attribute\Defaults;
use ManaPHP\Validating\Rule\Attribute\Email;
use ManaPHP\Validating\Rule\Attribute\IsReadonly;
use ManaPHP\Validating\Rule\Attribute\Length;
use ManaPHP\Validating\Rule\Attribute\MaxLength;
use ManaPHP\Validating\Rule\Attribute\Unique;

class Admin extends Model
{
    public const STATUS_INIT = 0;
    public const STATUS_ACTIVE = 1;
    public const STATUS_LOCKED = 2;

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
            'admin_name' => [new Length(4, 16), new Account(), new IsReadonly()],
            'email'      => [new Email(), new Unique()],
            'status'     => 'const',
            'white_ip'   => [new Defaults(''), new MaxLength(64)],
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

    public function create(): static
    {
        $this->salt = bin2hex(random_bytes(8));

        $input = Container::get(InputInterface::class);
        $this->password = $this->hashPassword($input->string('password'[new Length(1, 30)]));

        return parent::create();
    }

    public function update(): static
    {
        if ($this->hasChanged(['password'])) {
            $this->salt = bin2hex(random_bytes(8));
            $this->password = $this->hashPassword($this->password);
        }

        return parent::update();
    }
}