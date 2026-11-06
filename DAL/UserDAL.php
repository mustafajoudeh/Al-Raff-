<?php
declare(strict_types=1);
require_once __DIR__ . "./config/DataBase.php";
require_once __DIR__ . "../DTOs/User/UserResponseDTO.php";
require_once __DIR__ . "../DTOs/User/CreateUserDTO.php";

use App\Enum\User\Role;
class UserDAL
{
    public static function GetAllUsers(int $CurrentUserID, Role $Role = Role::Customer): array
    {
        try {
            $pdo = DataBase::Connect();

            $sql = " SELECT `UserName`, `Email`, `Phonenumber` , `Status` 
                     FROM `users` WHERE 
                    `Role` = :Role and `UserID` != :UserID; ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute(
                [
                    "UserID" => $CurrentUserID,
                    "Role" => $Role->value
                ]
            );

            $DataTable = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $Users = [];

            foreach ($DataTable as $row) {
                $Users[] = new UserResponseDTO(
                    $row["UserName"],
                    $row["Email"],
                    $row["Status"],
                    $Role,
                    $row["Phonenumber"],
                );
            }

            return $Users;
        } catch (Exception $e) {
            error_log($e->getMessage());
            return [];
        } finally {
            $pdo = null;
        }

    }

    public static function GetUserByID(int $UserID): UserResponseDTO|false
    {
        try {
            $pdo = DataBase::Connect();

            $sql = "SELECT `UserName`, `Email`, `Phonenumber` , `Role` ,`Status` FROM `users` 
                     WHERE UserID = :UserID;";

            $stmt = $pdo->prepare($sql);
            $stmt->execute(["UserID" => $UserID]);

            $Row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$Row)
                return false;

            return new UserResponseDTO(
                $Row['UserName'],
                $Row['Email'],
                $Row['Phonenumber'],
                $Row['Role'],
                $Row['Status']
            );

        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        } finally {
            $pdo = null;
        }

    }

    public static function AddUser(CreateUserDTO $CreateUserDTO): int
    {
        try {
            $pdo = DataBase::Connect();

            $sql = 'INSERT INTO `users`(`Role`, `Status`, `UserName`, `Email`, `Password`, `Phonenumber`) 
                    VALUES (:Role, :Status, :UserName, :Email, :Password, :Phonenumber)';

            $stmt = $pdo->prepare($sql);
            $stmt->execute(
                [
                    'Role' => $CreateUserDTO->Role,
                    'Status' => $CreateUserDTO->status,
                    'UserName' => $CreateUserDTO->username,
                    'passwrd' => password_hash($CreateUserDTO->password, PASSWORD_DEFAULT),
                    'Phonenumber' => $CreateUserDTO->PhoneNumber
                ]
            );
            $Row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$Row)
                return 0;

            return (int) $pdo->lastInsertId();
        } catch (Exception $e) {
            error_log($e->getMessage());
            return 0;
        } finally {
            $pdo = null;
        }


    }

    public static function UpdateUser(int $UserID, UserResponseDTO $UserResponseDTO): bool
    {
        try {
            $pdo = DataBase::Connect();
            $sql = 'UPDATE `users` 
                     SET `Role`=:Role,
                     `Status`=:Status,
                     `UserName`=:UserName,
                     `Email`=:Email,
                     `Phonenumber`=:Phonenumber 
                     WHERE UserID = :UserID';

            $stmt = $pdo->prepare($sql);
            $stmt->execute(
                [
                    'Role' => $UserResponseDTO->role,
                    'Status' => $UserResponseDTO->status,
                    'UserName' => $UserResponseDTO->username,
                    'Email' => $UserResponseDTO->email,
                    'Phonenumber' => $UserResponseDTO->PhoneNumber,
                    'UserID' => $UserID
                ]
            );

            return $stmt->rowCount() > 0;

        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        } finally {
            $pdo = null;
        }

    }

    public static function DeleteUser(int $UserID): bool
    {
        try {
            $pdo = DataBase::Connect();
            $sql = 'DELETE FROM `users` WHERE UserID = :UserID';
            $stmt = $pdo->prepare($sql);
            $stmt->execute(
                [
                    'UserID' => $UserID
                ]
            );
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        } finally {
            $pdo = null;
        }

    }

    public static function ActivateUser(int $UserID): bool
    {
        try {
            $pdo = DataBase::Connect();
            $sql = 'UPDATE `users` SET `Status`= "Active" WHERE UserID =:UserID';

            $stmt = $pdo->prepare($sql);
            $stmt->execute(
                [
                    'UserID' => $UserID
                ]
            );

            return $stmt->rowCount() > 0;

        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        } finally {
            $pdo = null;
        }

    }

    public static function DeActivateUser(int $UserID): bool
    {
        try {
            $pdo = DataBase::Connect();
            $sql = 'UPDATE `users` SET `Status`= "inActive" WHERE UserID =:UserID';

            $stmt = $pdo->prepare($sql);
            $stmt->execute(
                [
                    'UserID' => $UserID
                ]
            );

            return $stmt->rowCount() > 0;

        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        } finally {
            $pdo = null;
        }

    }


}