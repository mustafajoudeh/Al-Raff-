<?php
declare(strict_types=1);

require_once __DIR__ . "/../DAL/UserDAL.php";

require_once __DIR__ . "/../DTOs/User/CreateUserDTO.php";
require_once __DIR__ . "/../DTOs/User/UpdateUserDTO.php";
require_once __DIR__ . "/../DTOs/User/UserResponseDTO.php";
require_once __DIR__ . "/../DTOs/User/UserAuthDTO.php";

require_once __DIR__ . "/../Enum/User/Role.php";
require_once __DIR__ . "/../Enum/User/Status.php";

use App\Enum\User\Role;
use App\Enum\User\Status;

class UserDALTest
{
    private static int $Passed = 0;
    private static int $Failed = 0;

    private static function Assert(
        string $TestName,
        bool $Condition
    ): void {

        if ($Condition) {
            self::$Passed++;
            echo "[PASS] $TestName" . PHP_EOL;
        } else {
            self::$Failed++;
            echo "[FAIL] $TestName" . PHP_EOL;
        }
    }

    public static function Run(): void
    {
        echo "========== User DAL Tests ==========" . PHP_EOL;

        $UserID = 0;

        $Email = "test_" . bin2hex(random_bytes(5)) . "@example.com";

        $Password = "Test@123456";
        $NewPassword = "NewTest@123456";

        try {

            // =================================
            // 1. AddUser
            // =================================

            $CreateDTO = new CreateUserDTO(
                Role::Customer,
                Status::Active,
                "TestUser",
                $Email,
                $Password,
                "0790000000"
            );

            $UserID = UserDAL::AddUser($CreateDTO);

            self::Assert(
                "AddUser",
                $UserID > 0
            );


            if ($UserID <= 0) {
                return;
            }


            // =================================
            // 2. GetUserByID
            // =================================

            $User = UserDAL::GetUserByID($UserID);

            self::Assert(
                "GetUserByID",
                $User !== null &&
                $User->userId === $UserID
            );


            // =================================
            // 3. GetUserByEmail
            // =================================

            $UserByEmail = UserDAL::GetUserByEmail($Email);

            self::Assert(
                "GetUserByEmail",
                $UserByEmail !== null &&
                $UserByEmail->email === $Email
            );


            // =================================
            // 4. GetAllUsers
            // =================================

            $Users = UserDAL::GetAllUsers(
                $UserID,
                Role::Customer
            );

            self::Assert(
                "GetAllUsers",
                is_array($Users)
            );


            // =================================
            // 5. GetUserForLogin
            // =================================

            $AuthUser = UserDAL::GetUserForLogin($Email);

            self::Assert(
                "GetUserForLogin",
                $AuthUser !== null &&
                $AuthUser->userId === $UserID
            );


            // =================================
            // 6. UpdateUser
            // =================================

            $UpdateDTO = new UpdateUserDTO(
                Role::Customer,
                Status::Active,
                "UpdatedTestUser",
                $Email,
                "0791111111"
            );

            $Updated = UserDAL::UpdateUser(
                $UserID,
                $UpdateDTO
            );

            self::Assert(
                "UpdateUser",
                $Updated
            );


            // =================================
            // 7. checkPassword
            // =================================

            $PasswordCorrect = UserDAL::checkPassword(
                $Email,
                $Password
            );

            self::Assert(
                "checkPassword - Correct Password",
                $PasswordCorrect
            );


            $PasswordWrong = UserDAL::checkPassword(
                $Email,
                "WrongPassword"
            );

            self::Assert(
                "checkPassword - Wrong Password",
                !$PasswordWrong
            );


            // =================================
            // 8. ChangePassword
            // =================================

            $Changed = UserDAL::ChangePassword(
                $UserID,
                $NewPassword
            );

            self::Assert(
                "ChangePassword",
                $Changed
            );


            $NewPasswordCorrect = UserDAL::checkPassword(
                $UserID,
                $NewPassword
            );

            self::Assert(
                "Verify New Password",
                $NewPasswordCorrect
            );


            // =================================
            // 9. DeActivateUser
            // =================================

            $Deactivated = UserDAL::DeActivateUser($UserID);

            self::Assert(
                "DeActivateUser",
                $Deactivated
            );


            // =================================
            // 10. ActivateUser
            // =================================

            $Activated = UserDAL::ActivateUser($UserID);

            self::Assert(
                "ActivateUser",
                $Activated
            );


            // =================================
            // 11. DeleteUser
            // =================================

            $Deleted = UserDAL::DeleteUser($UserID);

            self::Assert(
                "DeleteUser",
                $Deleted
            );

            $DeletedUser = UserDAL::GetUserByID($UserID);

            self::Assert(
                "Verify DeleteUser",
                $DeletedUser === null
            );

            $UserID = 0;

        } catch (Throwable $e) {

            echo "[ERROR] " . $e->getMessage() . PHP_EOL;

        } finally {

            // Cleanup if a test fails before DeleteUser

            if ($UserID > 0) {
                UserDAL::DeleteUser($UserID);
            }

            echo PHP_EOL;
            echo "========== Test Results ==========" . PHP_EOL;

            echo "Passed: " . self::$Passed . PHP_EOL;
            echo "Failed: " . self::$Failed . PHP_EOL;

            echo "==================================" . PHP_EOL;
        }
    }
}

UserDALTest::Run();