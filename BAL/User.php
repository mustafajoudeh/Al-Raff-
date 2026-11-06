<?php
declare(strict_types=1);
require_once __DIR__ . "../DAL/UserDAL.php";
require_once __DIR__ . "../DTOs/User/UserResponseDTO.php";
require_once __DIR__ . "../DTOs/User/CreateUserDTO.php";

use App\Enum\User\Role;
use App\Enum\User\Status;

use App\Enum\Mode;
class User
{

    private int $userId;
    private string $username;
    private string $email;
    private string $password;
    private Status $status;
    private Role $Role;
    private ?string $PhoneNumber;

    private en_Mode $Mode;



    public function __construct(UserResponseDTO $response, en_Mode $Mode = en_Mode::Update)
    {
        $this->userId = $response->userId;
        $this->username = $response->username;
        $this->email = $response->email;
        $this->status = $response->status;
        $this->Role = $response->role;
        $this->PhoneNumber = $response->PhoneNumber;

        $this->Mode = $Mode;
    }


}