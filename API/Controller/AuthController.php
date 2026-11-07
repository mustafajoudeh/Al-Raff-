<?php
declare(strict_types=1);

require_once __DIR__ . '/../../DTOs/User/LoginDTO.php';
require_once __DIR__ . '/../../BAL/User/Auth.php';

final class AuthController
{
    public static function Login(Request $request): void
    {
        $body = $request->getBody();

        $email = trim((string) ($body['email'] ?? ''));
        $password = (string) ($body['password'] ?? '');

        $errors = [];
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email is required.';
        }
        if ($password === '') {
            $errors[] = 'Password is required.';
        }
        if ($errors) {
            Response::json(422, false, 'Validation failed', null, $errors);
        }

        if (!Auth::login(new LoginDTO($email, $password))) {

            Response::json(401, false, 'Invalid credentials');
        }

        Response::json(200, true, 'Logged in successfully', self::currentUser());
    }

    public static function Me(Request $request): void
    {
        Response::json(200, true, 'OK', self::currentUser());
    }

    public static function Logout(Request $request): void
    {
        Auth::logout();
        Response::json(200, true, 'Logged out successfully');
    }

    private static function currentUser(): array
    {
        return [
            'userId' => $_SESSION['UserID'],
            'username' => $_SESSION['UserName'],
            'email' => $_SESSION['Email'],
            'phoneNumber' => $_SESSION['PhoneNumber'],
            'role' => $_SESSION['Role'],
        ];
    }
}