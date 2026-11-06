<?php
declare(strict_types=1);
require_once __DIR__ . "/../config/DataBase.php";
require_once __DIR__ . "/../DTOs/User/LoginDTO.php";
require_once __DIR__ . "/../DTOs/User/UserResponseDTO.php";

use App\Enum\User\Status;
class Auth
{
    public static function Login(LoginDTO $loginDTO): ?UserResponseDTO
    {
        try {
            $User = UserDAL::GetUserForLogin($loginDTO->Email);

            if ($User === null)
                return null;

            if (!password_verify($loginDTO->Password, $User->password))
                return null;

            if ($User->status !== Status::Active)
                return null;

            return new UserResponseDTO(
                $User->userId,
                $User->role,
                $User->status,
                $User->username,
                $User->email,
                $User->PhoneNumber
            );

        } catch (Exception $e) {
            error_log($e->getMessage());
            return null;
        }
    }
}