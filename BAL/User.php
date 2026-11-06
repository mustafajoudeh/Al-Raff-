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

    public function __construct(UserResponseDTO|CreateUserDTO $response, en_Mode $Mode = en_Mode::Update)
    {
        $this->userId = $response->userId;
        $this->username = $response->username;
        $this->email = $response->email;
        $this->status = $response->status;
        $this->Role = $response->role;
        $this->password = $response->password;
        $this->PhoneNumber = $response->PhoneNumber;

        $this->Mode = $Mode;
    }

    public static function GetUserByID(int $UserID): ?UserResponseDTO
    {
        if ($UserID <= 0)
            return null;

        return UserDAL::GetUserByID($UserID);
    }

    public static function GetAllCustomers(int $CurrentUserID): ?array
    {
        if ($CurrentUserID <= 0)
            return null;

        return UserDAL::GetAllUsers(
            $CurrentUserID,
        );
    }

    public static function GetAllAdminUsers(int $CurrentUserID): ?array
    {
        if ($CurrentUserID <= 0)
            return null;

        return UserDAL::GetAllUsers(
            $CurrentUserID,
            Role::Admin
        );
    }

    public static function AddUser(CreateUserDTO $CreateUserDTO): int
    {

        if (
            empty(trim($CreateUserDTO->username)) ||
            !filter_var($CreateUserDTO->email, FILTER_VALIDATE_EMAIL) ||
            empty($CreateUserDTO->password)
        ) {
            return 0;
        }

        return UserDAL::AddUser($CreateUserDTO);
    }

    public static function UpdateUser(
        int $UserID,
        UpdateUserDTO $UpdateUserDTO
    ): bool {

        if ($UserID <= 0)
            return false;

        if (
            empty(trim($UpdateUserDTO->username)) ||
            !filter_var($UpdateUserDTO->email, FILTER_VALIDATE_EMAIL)
        ) {
            return false;
        }

        return UserDAL::UpdateUser($UserID, $UpdateUserDTO);
    }

    public static function DeleteUser(int $UserID): bool
    {
        if ($UserID <= 0)
            return false;

        return UserDAL::DeleteUser($UserID);
    }


    // ==================== ACTIVATE ====================

    public static function ActivateUser(int $UserID): bool
    {
        if ($UserID <= 0)
            return false;

        return UserDAL::ActivateUser($UserID);
    }


    // ==================== DEACTIVATE ====================

    public static function DeActivateUser(int $UserID): bool
    {
        if ($UserID <= 0)
            return false;

        return UserDAL::DeActivateUser($UserID);
    }


    // ==================== PASSWORD ====================

    public static function CheckPassword(
        int|string $Identifier,
        string $Password
    ): bool {

        if (empty($Password))
            return false;

        return UserDAL::CheckPassword(
            $Identifier,
            $Password
        );
    }


    public static function ChangePassword(
        int $UserID,
        string $NewPassword
    ): bool {

        if (
            $UserID <= 0 ||
            strlen($NewPassword) < 8
        ) {
            return false;
        }

        return UserDAL::ChangePassword(
            $UserID,
            $NewPassword
        );
    }
}