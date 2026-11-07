<?php
declare(strict_types=1);


require_once __DIR__ . "/../config/DataBase.php";
require_once __DIR__ . "/../DTOs/User/UserResponseDTO.php";
require_once __DIR__ . "/../DTOs/User/CreateUserDTO.php";
require_once __DIR__ . "/../DTOs/User/UpdateUserDTO.php";
require_once __DIR__ . "/../DTOs/User/UserAuthDTO.php";

use App\Enum\User\Role;
use App\Enum\User\Status;
class UserDAL
{
    public static function GetAllUsers(int $CurrentUserID, Role $Role = Role::Customer): ?array
    {
        try {
            $pdo = DataBase::Connect();

            $sql = " SELECT `UserID`, `UserName`, `Email`, `Phonenumber`, `Status`
                      FROM `users`
                      WHERE `Role` = :Role
                      AND `UserID` != :UserID;";

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
                    (int) $row["UserID"],
                    $Role,
                    Status::from($row["Status"]),
                    $row["UserName"],
                    $row["Email"],
                    $row["Phonenumber"]
                );
            }

            return $Users;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return null;
        } finally {
            $pdo = null;
        }

    }

    public static function GetUserByID(int $UserID): ?UserResponseDTO
    {
        try {
            $pdo = DataBase::Connect();

            $sql = "SELECT `UserID`, `Role`, `Status`, `UserName`,
                   `Email`, `Phonenumber`
                    FROM `users`
                    WHERE `UserID` = :UserID;";

            $stmt = $pdo->prepare($sql);
            $stmt->execute(["UserID" => $UserID]);

            $Row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$Row)
                return null;

            return new UserResponseDTO(
                (int) $Row['UserID'],
                Role::from($Row['Role']),
                Status::from($Row['Status']),
                $Row['UserName'],
                $Row['Email'],
                $Row['Phonenumber']
            );

        } catch (PDOException $e) {
            error_log($e->getMessage());
            return null;
        } finally {
            $pdo = null;
        }

    }

    public static function GetUserByEmail(string $Email): ?UserResponseDTO
    {
        try {
            $pdo = DataBase::Connect();

            $sql = "SELECT `UserID`, `UserName`, `Email`, `Phonenumber`,
                    `Role`, `Status`
                     FROM `users`
                     WHERE `Email` = :Email";

            $stmt = $pdo->prepare($sql);
            $stmt->execute(["Email" => $Email]);

            $Row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$Row)
                return null;

            return new UserResponseDTO(
                (int) $Row['UserID'],
                Role::from($Row['Role']),
                Status::from($Row['Status']),
                $Row['UserName'],
                $Row['Email'],
                $Row['Phonenumber']
            );

        } catch (PDOException $e) {
            error_log($e->getMessage());
            return null;
        } finally {
            $pdo = null;
        }

    }

    public static function GetUserForLogin(string $Email): ?UserAuthDTO
    {
        try {
            $pdo = DataBase::Connect();

            $sql = "SELECT `UserID`, `Role`, `Status`, `UserName`
                , `Email`, `Password`, `Phonenumber`
                FROM `users`
                WHERE `Email` = :Email
                LIMIT 1";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'Email' => $Email
            ]);

            $Row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$Row) {
                return null;
            }

            return new UserAuthDTO(
                (int) $Row['UserID'],
                Role::from($Row['Role']),
                Status::from($Row['Status']),
                $Row['UserName'],
                $Row['Email'],
                $Row['Password'],
                $Row['Phonenumber']
            );

        } catch (PDOException $e) {
            error_log($e->getMessage());
            return null;
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
                    'Role' => $CreateUserDTO->role->value,
                    'Status' => $CreateUserDTO->status->value,
                    'UserName' => $CreateUserDTO->username,
                    'Password' => password_hash(
                        $CreateUserDTO->password,
                        PASSWORD_DEFAULT
                    ),
                    'Email' => $CreateUserDTO->email,
                    'Phonenumber' => $CreateUserDTO->PhoneNumber
                ]
            );

            return (int) $pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return 0;
        } finally {
            $pdo = null;
        }


    }

    public static function UpdateUser(int $UserID, UpdateUserDTO $UpdateUserDTO): bool
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
                    'Role' => $UpdateUserDTO->role->value,
                    'Status' => $UpdateUserDTO->status->value,
                    'UserName' => $UpdateUserDTO->username,
                    'Email' => $UpdateUserDTO->email,
                    'Phonenumber' => $UpdateUserDTO->PhoneNumber,
                    'UserID' => $UserID
                ]
            );

            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {
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
        } catch (PDOException $e) {
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
            $sql = 'UPDATE `users` SET `Status` = :Status WHERE UserID = :UserID';

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'Status' => Status::Active->value,
                'UserID' => $UserID
            ]);

            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {
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
            $sql = 'UPDATE `users` SET `Status` = :Status WHERE UserID = :UserID';

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'Status' => Status::InActive->value,
                'UserID' => $UserID
            ]);

            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        } finally {
            $pdo = null;
        }

    }

    public static function checkPassword(int|string $identifier, string $password): bool
    {
        try {
            $pdo = DataBase::Connect();


            $column = is_int($identifier) ? 'UserID' : 'Email';

            $sql = "SELECT `Password` FROM `users` WHERE `$column` = :identifier LIMIT 1";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'identifier' => $identifier
            ]);

            $hashedPassword = $stmt->fetchColumn();

            return $hashedPassword !== false
                && password_verify($password, $hashedPassword);

        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        } finally {
            $pdo = null;
        }
    }


    public static function ChangePassword(int $UserID, string $newPassword): bool
    {
        try {
            $pdo = DataBase::Connect();
            $sql = 'UPDATE `users` 
                    set `Password` = :Password 
                    WHERE UserID = :UserID';

            $stmt = $pdo->prepare($sql);
            $stmt->execute(
                [
                    "UserID" => $UserID,
                    "Password" => password_hash($newPassword, PASSWORD_DEFAULT)
                ]
            );
            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        } finally {
            $pdo = null;
        }
    }

    public static function IsEmailExisted(string $Email, int $ExcludeUserID = 0): bool
    {
        try {
            $pdo = DataBase::Connect();
            $sql = "SELECT 1 FROM users WHERE Email = :Email AND UserID != :ExcludeUserID LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                "Email" => $Email,
                "ExcludeUserID" => $ExcludeUserID
            ]);
            return $stmt->fetchColumn() !== false;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        } finally {
            $pdo = null;
        }
    }

    public static function IsUserNameExisted(string $UserName, int $ExcludeUserID = 0): bool
    {
        try {
            $pdo = DataBase::Connect();
            $sql = "SELECT 1 FROM users WHERE UserName = :UserName AND UserID != :ExcludeUserID LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                "UserName" => $UserName,
                "ExcludeUserID" => $ExcludeUserID
            ]);
            return $stmt->fetchColumn() !== false;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        } finally {
            $pdo = null;
        }
    }


}