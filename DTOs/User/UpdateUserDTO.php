<?php
declare(strict_types=1);

use App\Enum\User\Role;
use App\Enum\User\Status;

class UpdateUserDTO
{
    public function __construct(
        public readonly Role $role,
        public readonly Status $status,
        public readonly string $username,
        public readonly string $email,
        public readonly ?string $PhoneNumber
    ) {
    }
}