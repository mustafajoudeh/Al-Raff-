<?php
declare(strict_types=1);

require_once __DIR__ . '/../../BAL/User/User.php';

use App\Enum\User\Role;
use App\Enum\User\Status;

final class AdminController
{
    public static function Customers(Request $request): void
    {
        $users = User::GetAllCustomers(self::currentAdminId());

        if ($users === null) {
            Response::json(500, false, 'Could not load customers');
        }

        Response::json(200, true, 'OK', $users);
    }

    public static function Admins(Request $request): void
    {
        $users = User::GetAllAdminUsers(self::currentAdminId());

        if ($users === null) {
            Response::json(500, false, 'Could not load admins');
        }

        Response::json(200, true, 'OK', $users);
    }

    public static function GetUser(Request $request): void
    {
        $user = self::findOrFail(self::userId($request));

        Response::json(200, true, 'OK', $user);
    }

    public static function AddUser(Request $request): void
    {
        $body = $request->getBody();

        $role = Role::tryFrom((string) ($body['role'] ?? 'Customer'));
        $status = Status::tryFrom((string) ($body['status'] ?? 'Active'));

        $errors = [];

        if ($role === null) {
            $errors[] = 'Invalid role.';
        }

        if ($status === null) {
            $errors[] = 'Invalid status.';
        }

        if ($errors) {
            Response::json(422, false, 'Validation failed', null, $errors);
        }

        $phone = isset($body['phoneNumber']) ? trim((string) $body['phoneNumber']) : null;

        $dto = new CreateUserDTO(
            role: $role,
            status: $status,
            username: trim((string) ($body['username'] ?? '')),
            email: trim((string) ($body['email'] ?? '')),
            password: (string) ($body['password'] ?? ''),
            PhoneNumber: $phone === '' ? null : $phone
        );

        $user = new User($dto);

        if (!$user->Save()) {
            self::saveFailed($user->GetErrorMessages(), 'Could not create user');
        }

        Response::json(201, true, 'User created successfully', [
            'userId' => $user->GetUserID()
        ]);
    }

    public static function UpdateUser(Request $request): void
    {
        $id = self::userId($request);
        $current = self::findOrFail($id);
        $body = $request->getBody();

        $role = Role::tryFrom((string) ($body['role'] ?? $current->role->value));
        $status = Status::tryFrom((string) ($body['status'] ?? $current->status->value));

        $errors = [];

        if ($role === null) {
            $errors[] = 'Invalid role.';
        }

        if ($status === null) {
            $errors[] = 'Invalid status.';
        }

        if ($errors) {
            Response::json(422, false, 'Validation failed', null, $errors);
        }

        if ($id === self::currentAdminId() && ($role !== Role::Admin || $status !== Status::Active)) {
            Response::json(403, false, 'You cannot change your own role or status');
        }

        if (array_key_exists('phoneNumber', $body)) {
            $phone = trim((string) $body['phoneNumber']);
            $phone = $phone === '' ? null : $phone;
        } else {
            $phone = $current->PhoneNumber;
        }

        $dto = new UpdateUserDTO(
            role: $role,
            status: $status,
            username: trim((string) ($body['username'] ?? $current->username)),
            email: trim((string) ($body['email'] ?? $current->email)),
            PhoneNumber: $phone
        );

        $user = User::FindUserByID($id);

        if ($user === null) {
            Response::json(404, false, 'User not found');
        }

        $user->Fill($dto);

        if (!$user->Save()) {
            self::saveFailed($user->GetErrorMessages(), 'Could not update user');
        }

        Response::json(200, true, 'User updated successfully');
    }

    public static function DeleteUser(Request $request): void
    {
        $id = self::userId($request);
        self::findOrFail($id);

        if ($id === self::currentAdminId()) {
            Response::json(403, false, 'You cannot delete your own account');
        }

        if (!User::DeleteUser($id)) {
            Response::json(500, false, 'Could not delete user');
        }

        Response::json(200, true, 'User deleted successfully');
    }

    public static function ActivateUser(Request $request): void
    {
        $id = self::userId($request);
        self::findOrFail($id);

        if (!User::ActivateUserByID($id)) {
            Response::json(500, false, 'Could not activate user');
        }

        Response::json(200, true, 'User activated successfully');
    }

    public static function DeActivateUser(Request $request): void
    {
        $id = self::userId($request);
        self::findOrFail($id);

        if ($id === self::currentAdminId()) {
            Response::json(403, false, 'You cannot deactivate your own account');
        }

        if (!User::DeActivateUserByID($id)) {
            Response::json(500, false, 'Could not deactivate user');
        }

        Response::json(200, true, 'User deactivated successfully');
    }

    public static function ChangePassword(Request $request): void
    {
        $id = self::userId($request);
        self::findOrFail($id);

        $password = (string) ($request->getBody()['password'] ?? '');

        $validation = new UserValidation();

        if (!$validation->ValidateChangePassword($id, $password)) {
            Response::json(422, false, 'Validation failed', null, $validation->GetErrorMessages());
        }

        if (!User::ChangePassword($id, $password)) {
            Response::json(500, false, 'Could not change password');
        }

        Response::json(200, true, 'Password changed successfully');
    }

    private static function currentAdminId(): int
    {
        return (int) ($_SESSION['UserID'] ?? 0);
    }

    private static function userId(Request $request): int
    {
        $id = $request->getParam('id');

        if ($id === null || !ctype_digit($id) || (int) $id <= 0) {
            Response::json(400, false, 'Invalid user id');
        }

        return (int) $id;
    }

    private static function findOrFail(int $id): UserResponseDTO
    {
        $user = User::GetDTOByID($id);

        if ($user === null) {
            Response::json(404, false, 'User not found');
        }

        return $user;
    }

    private static function saveFailed(array $errors, string $message): void
    {
        if ($errors) {
            Response::json(422, false, 'Validation failed', null, $errors);
        }

        Response::json(500, false, $message);
    }
}