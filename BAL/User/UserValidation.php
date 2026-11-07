<?php
declare(strict_types=1);

require_once __DIR__ . "/../../DAL/UserDAL.php";
require_once __DIR__ . "/../../DTOs/User/CreateUserDTO.php";
require_once __DIR__ . "/../../DTOs/User/UpdateUserDTO.php";

class UserValidation
{
    private array $ErrorMessages = [];

    public function GetErrorMessages(): array
    {
        return $this->ErrorMessages;
    }

    private function ValidateUserData(
        string $username,
        string $email
    ): bool {

        if (empty(trim($username))) {
            $this->ErrorMessages[] = "Username is required.";
        }

        if (empty(trim($email))) {
            $this->ErrorMessages[] = "Email is required.";
        } elseif (!filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
            $this->ErrorMessages[] = "Invalid email format.";
        }

        return empty($this->ErrorMessages);
    }

    public function ValidateAdd(CreateUserDTO $data): bool
    {
        $this->ErrorMessages = [];

        $this->ValidateUserData(
            $data->username,
            $data->email
        );

        if (strlen($data->password) < 8) {
            $this->ErrorMessages[] =
                "Password must be at least 8 characters.";
        }

        if (
            !empty(trim($data->email)) &&
            UserDAL::IsEmailExisted(trim($data->email))
        ) {
            $this->ErrorMessages[] = "Email already exists.";
        }

        if (
            !empty(trim($data->username)) &&
            UserDAL::IsUserNameExisted(trim($data->username))
        ) {
            $this->ErrorMessages[] = "Username already exists.";
        }

        return empty($this->ErrorMessages);
    }

    public function ValidateUpdate(
        UpdateUserDTO $data,
        int $UserID,

    ): bool {

        $this->ErrorMessages = [];

        if ($UserID <= 0) {
            $this->ErrorMessages[] = "Invalid User ID.";
            return false;
        }

        $this->ValidateUserData(
            $data->username,
            $data->email
        );

        if (UserDAL::IsEmailExisted(trim($data->email), $UserID)) {
            $this->ErrorMessages[] = "Email already exists.";
        }

        if (UserDAL::IsUserNameExisted(trim($data->username), $UserID)) {
            $this->ErrorMessages[] = "Username already exists.";
        }

        return empty($this->ErrorMessages);
    }

    public function ValidateChangePassword(
        int $UserID,
        string $NewPassword
    ): bool {

        $this->ErrorMessages = [];

        if ($UserID <= 0) {
            $this->ErrorMessages[] = "Invalid User ID.";
        }

        if (empty($NewPassword)) {
            $this->ErrorMessages[] = "Password is required.";
        } elseif (strlen($NewPassword) < 8) {
            $this->ErrorMessages[] =
                "Password must be at least 8 characters.";
        }

        return empty($this->ErrorMessages);
    }
}