<?php
declare(strict_types=1);
require_once __DIR__ . "/../../DAL/UserDAL.php";
require_once __DIR__ . "/../../DTOs/User/UserResponseDTO.php";
require_once __DIR__ . "/../../DTOs/User/CreateUserDTO.php";
require_once __DIR__ . "/UserValidation.php";
require_once __DIR__ . "/../../DTOs/User/UpdateUserDTO.php";

use App\Enum\User\Role;
use App\Enum\User\Status;

use App\Enum\en_Mode;
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

    private array $ErrorMessages = [];

    private function _GetCreateUserDTO(): CreateUserDTO
    {
        return new CreateUserDTO(
            role: $this->Role,
            status: $this->status,
            username: $this->username,
            email: $this->email,
            password: $this->password,
            PhoneNumber: $this->PhoneNumber
        );
    }

    private function _GetUpdateUserDTO(): UpdateUserDTO
    {
        return new UpdateUserDTO(
            role: $this->Role,
            status: $this->status,
            username: $this->username,
            email: $this->email,
            PhoneNumber: $this->PhoneNumber
        );
    }

    public function GetErrorMessages(): array
    {
        return $this->ErrorMessages;
    }

    public function GetUserID(): int
    {
        return $this->userId;
    }

    public function Fill(UpdateUserDTO $data): void
    {
        $this->username = $data->username;
        $this->email = $data->email;
        $this->status = $data->status;
        $this->Role = $data->role;
        $this->PhoneNumber = $data->PhoneNumber;
    }

    public static function GetDTOByID(int $UserID): ?UserResponseDTO
    {
        if ($UserID <= 0)
            return null;

        return UserDAL::GetUserByID($UserID);
    }

    public function __construct(UserResponseDTO|CreateUserDTO $data)
    {
        $this->username = $data->username;
        $this->email = $data->email;
        $this->status = $data->status;
        $this->Role = $data->role;
        $this->PhoneNumber = $data->PhoneNumber;

        if ($data instanceof CreateUserDTO) {
            $this->userId = 0;
            $this->password = $data->password;
            $this->Mode = en_Mode::Add;
        } else {
            $this->userId = $data->userId;
            $this->password = '';
            $this->Mode = en_Mode::Update;
        }
    }

    public static function FindUserByID(int $UserID): ?User
    {
        if ($UserID <= 0)
            return null;

        $UserDTO = UserDAL::GetUserByID($UserID);

        if ($UserDTO === null)
            return null;

        return new User($UserDTO);
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

    private function _AddNewUser(): bool
    {
        $CreateDTO = $this->_GetCreateUserDTO();

        $Validation = new UserValidation();
        if (!$Validation->ValidateAdd($CreateDTO)) {
            $this->ErrorMessages = $Validation->GetErrorMessages();
            return false;
        }

        $NewUserID = UserDAL::AddUser($CreateDTO);

        if ($NewUserID <= 0) {
            $this->ErrorMessages = $Validation->GetErrorMessages();

            return false;
        }

        $this->userId = $NewUserID;
        $this->Mode = en_Mode::Update;
        $this->password = '';

        return true;
    }

    private function _UpdateUser(): bool
    {
        if ($this->userId <= 0)
            return false;

        $UpdateDTO = $this->_GetUpdateUserDTO();

        $Validation = new UserValidation();
        if (
            !$Validation->ValidateUpdate(
                $UpdateDTO,
                $this->userId
            )
        ) {
            $this->ErrorMessages = $Validation->GetErrorMessages();
            return false;
        }

        return UserDAL::UpdateUser(
            $this->userId,
            $UpdateDTO
        );
    }

    public function Save(): bool
    {
        return match ($this->Mode) {

            en_Mode::Add => $this->_AddNewUser(),

            en_Mode::Update => $this->_UpdateUser()

        };
    }


    public static function DeleteUser(int $UserID): bool
    {
        if ($UserID <= 0)
            return false;

        return UserDAL::DeleteUser($UserID);
    }

    public function ActivateUser(): bool
    {
        if ($this->userId <= 0)
            return false;

        return UserDAL::ActivateUser($this->userId);
    }

    public static function ActivateUserByID(int $UserID): bool
    {
        if ($UserID <= 0)
            return false;

        return UserDAL::ActivateUser($UserID);
    }

    public function DeActivateUser(): bool
    {
        if ($this->userId <= 0)
            return false;

        return UserDAL::DeActivateUser($this->userId);
    }

    public static function DeActivateUserByID(int $UserID): bool
    {
        if ($UserID <= 0)
            return false;

        return UserDAL::DeActivateUser($UserID);
    }

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