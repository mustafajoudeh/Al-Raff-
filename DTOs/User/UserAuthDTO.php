<?php
declare(strict_types=1);

use App\Enum\User\Role;
use App\Enum\User\Status;

class UserAuthDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly Role $role,
        public readonly Status $status,
        public readonly string $username,
        public readonly string $email,
        public readonly string $password,
        public readonly ?string $PhoneNumber
    ) {
    }
}