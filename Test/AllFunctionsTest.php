<?php
declare(strict_types=1);

/**
 * Integration tests for the public functions in Al-Raff DAL, BAL and validation.
 * Run from the project root: php Test/AllFunctionsTest.php
 * Uses the configured MySQL database and creates temporary test users.
 */

require_once __DIR__ . '/../Enum/Mode.php';
require_once __DIR__ . '/../Enum/User/Role.php';
require_once __DIR__ . '/../Enum/User/Status.php';

require_once __DIR__ . '/../DTOs/User/CreateUserDTO.php';
require_once __DIR__ . '/../DTOs/User/UpdateUserDTO.php';
require_once __DIR__ . '/../DTOs/User/LoginDTO.php';
require_once __DIR__ . '/../DTOs/User/UserResponseDTO.php';
require_once __DIR__ . '/../DTOs/User/UserAuthDTO.php';

require_once __DIR__ . '/../config/DataBase.php';
require_once __DIR__ . '/../DAL/UserDAL.php';
require_once __DIR__ . '/../DAL/Auth.php';
require_once __DIR__ . '/../BAL/User/UserValidation.php';
require_once __DIR__ . '/../BAL/User/User.php';
require_once __DIR__ . '/../BAL/User/Auth.php';

use App\Enum\User\Role;
use App\Enum\User\Status;

final class AllFunctionsTest
{
    private static int $passed = 0;
    private static int $failed = 0;
    private static array $createdUserIds = [];
    private static string $suffix;
    private static string $password = 'Test@123456';
    private static string $newPassword = 'Changed@123456';

    private static function test(string $name, callable $callback): void
    {
        try {
            $result = $callback();
            if ($result === true) {
                self::$passed++;
                echo "[PASS] {$name}" . PHP_EOL;
            } else {
                self::$failed++;
                echo "[FAIL] {$name}" . PHP_EOL;
            }
        } catch (Throwable $e) {
            self::$failed++;
            echo "[ERROR] {$name}: {$e->getMessage()}" . PHP_EOL;
        }
    }

    private static function createDTO(
        string $username,
        string $email,
        Role $role = Role::Customer,
        Status $status = Status::Active,
        ?string $phone = '0790000000',
        ?string $password = null
    ): CreateUserDTO {
        return new CreateUserDTO(
            $role,
            $status,
            $username,
            $email,
            $password ?? self::$password,
            $phone
        );
    }

    private static function updateDTO(
        string $username,
        string $email,
        Role $role = Role::Customer,
        Status $status = Status::Active,
        ?string $phone = '0791111111'
    ): UpdateUserDTO {
        return new UpdateUserDTO($role, $status, $username, $email, $phone);
    }

    private static function remember(int $id): int
    {
        if ($id > 0) {
            self::$createdUserIds[] = $id;
        }
        return $id;
    }

    private static function executeTests(): void
    {
        self::$suffix = bin2hex(random_bytes(5));
        $username = 'test_' . self::$suffix;
        $email = 'test_' . self::$suffix . '@example.com';
        $adminUsername = 'admin_' . self::$suffix;
        $adminEmail = 'admin_' . self::$suffix . '@example.com';
        $userId = 0;
        $adminId = 0;
        $balUser = null;

        echo PHP_EOL . '========== Al-Raff: All Functions Tests ==========' . PHP_EOL;

        // Database connection
        self::test('DataBase::Connect returns PDO', static function (): bool {
            return DataBase::Connect() instanceof PDO;
        });

        // -------------------- DAL: create and retrieve --------------------
        self::test('UserDAL::AddUser', static function () use ($username, $email, &$userId): bool {
            $userId = self::remember(UserDAL::AddUser(self::createDTO($username, $email)));
            return $userId > 0;
        });

        self::test('UserDAL::GetUserByID', static function () use (&$userId): bool {
            $user = UserDAL::GetUserByID($userId);
            return $user instanceof UserResponseDTO && $user->userId === $userId;
        });

        self::test('UserDAL::GetUserByEmail', static function () use ($email): bool {
            $user = UserDAL::GetUserByEmail($email);
            return $user instanceof UserResponseDTO && $user->email === $email;
        });

        self::test('UserDAL::GetUserForLogin', static function () use ($email, &$userId): bool {
            $user = UserDAL::GetUserForLogin($email);
            return $user instanceof UserAuthDTO
                && $user->userId === $userId
                && password_verify(self::$password, $user->password);
        });

        self::test('UserDAL::GetAllUsers filters customers and excludes current user', static function () use (&$userId): bool {
            $users = UserDAL::GetAllUsers($userId, Role::Customer);
            if (!is_array($users)) return false;
            foreach ($users as $user) {
                if ($user->userId === $userId || $user->role !== Role::Customer) return false;
            }
            return true;
        });

        self::test('UserDAL::IsEmailExisted true/false', static function () use ($email): bool {
            return UserDAL::IsEmailExisted($email)
                && !UserDAL::IsEmailExisted('missing_' . self::$suffix . '@example.com');
        });

        self::test('UserDAL::IsUserNameExisted true/false', static function () use ($username): bool {
            return UserDAL::IsUserNameExisted($username)
                && !UserDAL::IsUserNameExisted('missing_' . self::$suffix);
        });

        // -------------------- DAL: update --------------------
        self::test('UserDAL::UpdateUser', static function () use (&$userId, &$username, &$email): bool {
            $username = 'updated_' . self::$suffix;
            $email = 'updated_' . self::$suffix . '@example.com';
            $ok = UserDAL::UpdateUser($userId, self::updateDTO($username, $email));
            $saved = UserDAL::GetUserByID($userId);
            return $ok && $saved !== null
                && $saved->username === $username
                && $saved->email === $email;
        });

        // -------------------- DAL: password --------------------
        self::test('UserDAL::checkPassword by email (correct and wrong)', static function () use (&$email): bool {
            return UserDAL::checkPassword($email, self::$password)
                && !UserDAL::checkPassword($email, 'WrongPassword');
        });

        self::test('UserDAL::checkPassword by user ID', static function () use (&$userId): bool {
            return UserDAL::checkPassword($userId, self::$password);
        });

        self::test('UserDAL::ChangePassword and verify new password', static function () use (&$userId, &$email): bool {
            $changed = UserDAL::ChangePassword($userId, self::$newPassword);
            return $changed
                && UserDAL::checkPassword($userId, self::$newPassword)
                && !UserDAL::checkPassword($email, self::$password);
        });

        // -------------------- DAL: activate/deactivate --------------------
        self::test('UserDAL::DeActivateUser', static function () use (&$userId): bool {
            return UserDAL::DeActivateUser($userId)
                && UserDAL::GetUserByID($userId)?->status === Status::InActive;
        });

        self::test('UserDAL::ActivateUser', static function () use (&$userId): bool {
            return UserDAL::ActivateUser($userId)
                && UserDAL::GetUserByID($userId)?->status === Status::Active;
        });

        // -------------------- DAL/BAL Auth --------------------
        self::test('AuthDAL::Login accepts valid active credentials', static function () use (&$email): bool {
            $result = AuthDAL::Login(new LoginDTO($email, self::$newPassword));
            return $result instanceof UserResponseDTO;
        });

        self::test('AuthDAL::Login rejects wrong password', static function () use (&$email): bool {
            return AuthDAL::Login(new LoginDTO($email, 'WrongPassword')) === null;
        });

        self::test('AuthDAL::Login rejects inactive user', static function () use (&$userId, &$email): bool {
            UserDAL::DeActivateUser($userId);
            $result = AuthDAL::Login(new LoginDTO($email, self::$newPassword));
            UserDAL::ActivateUser($userId);
            return $result === null;
        });

        self::test('BAL Auth::login creates session data', static function () use (&$email): bool {
            $ok = Auth::login(new LoginDTO($email, self::$newPassword));
            return $ok
                && session_status() === PHP_SESSION_ACTIVE
                && isset($_SESSION['UserID'], $_SESSION['UserName'], $_SESSION['Email'], $_SESSION['Role']);
        });

        self::test('BAL Auth::logout clears and destroys session', static function (): bool {
            $ok = Auth::logout();
            return $ok && session_status() !== PHP_SESSION_ACTIVE && empty($_SESSION);
        });

        // -------------------- UserValidation --------------------
        self::test('UserValidation::GetErrorMessages initially empty', static function (): bool {
            return (new UserValidation())->GetErrorMessages() === [];
        });

        self::test('UserValidation::ValidateAdd accepts valid unique data', static function (): bool {
            $validation = new UserValidation();
            return $validation->ValidateAdd(self::createDTO(
                'validation_' . self::$suffix,
                'validation_' . self::$suffix . '@example.com'
            )) && $validation->GetErrorMessages() === [];
        });

        self::test('UserValidation::ValidateAdd rejects duplicate email and username', static function () use ($username, $email): bool {
            $validation = new UserValidation();
            $valid = $validation->ValidateAdd(self::createDTO($username, $email));
            $messages = implode(' ', $validation->GetErrorMessages());
            return !$valid && str_contains($messages, 'Email already exists.')
                && str_contains($messages, 'Username already exists.');
        });

        self::test('UserValidation::ValidateAdd rejects invalid fields', static function (): bool {
            $validation = new UserValidation();
            $valid = $validation->ValidateAdd(self::createDTO('', 'not-an-email', password: '123'));
            return !$valid && count($validation->GetErrorMessages()) >= 3;
        });

        self::test('UserValidation::ValidateUpdate accepts unique replacement values', static function (): bool {
            $validation = new UserValidation();
            return $validation->ValidateUpdate(
                self::updateDTO('validation_update_' . self::$suffix, 'validation_update_' . self::$suffix . '@example.com'),
                999999999
            );
        });

        self::test('UserValidation::ValidateUpdate rejects invalid ID', static function (): bool {
            $validation = new UserValidation();
            return !$validation->ValidateUpdate(
                self::updateDTO('valid_name', 'valid_' . self::$suffix . '@example.com'),
                0
            ) && in_array('Invalid User ID.', $validation->GetErrorMessages(), true);
        });

        self::test('UserValidation::ValidateChangePassword accepts valid password', static function (): bool {
            $validation = new UserValidation();
            return $validation->ValidateChangePassword(1, 'ValidPass123');
        });

        self::test('UserValidation::ValidateChangePassword rejects invalid ID/password', static function (): bool {
            $validation = new UserValidation();
            return !$validation->ValidateChangePassword(0, '123')
                && count($validation->GetErrorMessages()) === 2;
        });

        // -------------------- BAL User operations --------------------
        self::test('BAL User::Save adds a user', static function () use (&$balUser): bool {
            $dto = self::createDTO(
                'bal_' . self::$suffix,
                'bal_' . self::$suffix . '@example.com'
            );
            $balUser = new User($dto);
            $ok = $balUser->Save();
            if ($ok) {
                $found = UserDAL::GetUserByEmail('bal_' . self::$suffix . '@example.com');
                if ($found !== null) self::remember($found->userId);
            }
            return $ok;
        });

        self::test('BAL User::FindUserByID', static function () use (&$balUser): bool {
            $found = User::FindUserByID(self::findBalUserId());
            return $found instanceof User;
        });

        self::test('BAL User::GetAllCustomers', static function () use (&$userId): bool {
            return is_array(User::GetAllCustomers($userId));
        });

        self::test('BAL User::GetAllAdminUsers', static function () use (&$userId): bool {
            return is_array(User::GetAllAdminUsers($userId));
        });

        self::test('BAL User::Save updates an existing user', static function () use (&$balUser): bool {
            if (!$balUser instanceof User) return false;
            // The class currently exposes no setters; reflection changes test data only,
            // allowing Save()'s Update branch to be exercised without changing production API.
            $ref = new ReflectionClass($balUser);
            foreach ([
                'username' => 'bal_updated_' . self::$suffix,
                'email' => 'bal_updated_' . self::$suffix . '@example.com',
                'PhoneNumber' => '0792222222'
            ] as $property => $value) {
                $ref->getProperty($property)->setValue($balUser, $value);
            }
            return $balUser->Save();
        });

        self::test('BAL User::CheckPassword', static function (): bool {
            return User::CheckPassword('bal_updated_' . self::$suffix . '@example.com', self::$password)
                && !User::CheckPassword('bal_updated_' . self::$suffix . '@example.com', 'bad');
        });

        self::test('BAL User::ChangePassword', static function (): bool {
            $dto = UserDAL::GetUserByEmail('bal_updated_' . self::$suffix . '@example.com');
            return $dto !== null
                && User::ChangePassword($dto->userId, 'BalChanged@123')
                && User::CheckPassword($dto->userId, 'BalChanged@123');
        });

        self::test('BAL User instance DeActivateUser and ActivateUser', static function () use (&$balUser): bool {
            if (!$balUser instanceof User) return false;
            $ref = new ReflectionClass($balUser);
            $id = self::findBalUserId();
            $ref->getProperty('userId')->setValue($balUser, $id);
            return $balUser->DeActivateUser()
                && $balUser->ActivateUser();
        });

        self::test('BAL User static DeActivateUserByID and ActivateUserByID', static function (): bool {
            $id = self::findBalUserId();
            return User::DeActivateUserByID($id)
                && User::ActivateUserByID($id);
        });

        // -------------------- Admin-role list coverage --------------------
        self::test('Create admin fixture for admin list', static function () use ($adminUsername, $adminEmail, &$adminId): bool {
            $adminId = self::remember(UserDAL::AddUser(self::createDTO(
                $adminUsername,
                $adminEmail,
                Role::Admin
            )));
            return $adminId > 0;
        });

        self::test('GetAllUsers returns admin role when requested', static function () use (&$adminId, &$userId): bool {
            $admins = UserDAL::GetAllUsers($userId, Role::Admin);
            if (!is_array($admins)) return false;
            foreach ($admins as $admin) {
                if ($admin->role !== Role::Admin) return false;
            }
            return in_array($adminId, array_map(static fn($u) => $u->userId, $admins), true);
        });

        self::test('BAL User::DeleteUser', static function () use (&$adminId): bool {
            $ok = User::DeleteUser($adminId);
            if ($ok) self::$createdUserIds = array_values(array_diff(self::$createdUserIds, [$adminId]));
            return $ok && UserDAL::GetUserByID($adminId) === null;
        });

        self::test('BAL User::DeleteUser rejects invalid ID', static function (): bool {
            return !User::DeleteUser(0);
        });

        // -------------------- Cleanup --------------------
        foreach (array_unique(self::$createdUserIds) as $id) {
            UserDAL::DeleteUser($id);
        }

        echo PHP_EOL . '=============== Test Summary ===============' . PHP_EOL;
        echo 'Passed: ' . self::$passed . PHP_EOL;
        echo 'Failed: ' . self::$failed . PHP_EOL;
        echo 'Total:  ' . (self::$passed + self::$failed) . PHP_EOL;
        echo '============================================' . PHP_EOL;
    }

    private static function findBalUserId(): int
    {
        $user = UserDAL::GetUserByEmail('bal_updated_' . self::$suffix . '@example.com');
        if ($user === null) {
            $user = UserDAL::GetUserByEmail('bal_' . self::$suffix . '@example.com');
        }
        return $user?->userId ?? 0;
    }

    public static function Run(): void
    {
        try {
            self::executeTests();
        } catch (Throwable $e) {
            echo '[FATAL] ' . $e->getMessage() . PHP_EOL;
            foreach (self::$createdUserIds as $id) {
                try { UserDAL::DeleteUser($id); } catch (Throwable) {}
            }
            exit(1);
        }

        if (self::$failed > 0) {
            exit(1);
        }
    }
}

AllFunctionsTest::Run();
