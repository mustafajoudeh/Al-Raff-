<?php
declare(strict_types=1);

use App\Enum\User\Role;
use App\Enum\User\Status;

final class CreateUserDTO
{
    public Role $Role;
    public Status $status;
    public string $username;
    public string $email;
    public string $password;
    public ?string $PhoneNumber;
}