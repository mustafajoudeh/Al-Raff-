<?php
declare(strict_types=1);

use App\Enum\User\Role;
use App\Enum\User\Status;

final class UserResponseDTO
{
    public function __construct(
        string $username,
        string $email,
        Status $status,
        Role $role,
        ?string $PhoneNumber = null
    ) {
        $this->username = $username;
        $this->email = $email;
        $this->status = $status;
        $this->role = $role;
        $this->PhoneNumber = $PhoneNumber;
    }

    public int $userId;

    public string $username;
    public string $email;
    public ?string $PhoneNumber;
    public Status $status;
    public Role $role;

}