<?php
declare(strict_types=1);
require_once __DIR__ . "/../../DAL/Auth.php";


use App\Enum\User\Status;

class Auth
{
    public static function login(LoginDTO $loginDTO): bool
    {
        $User = AuthDAL::login($loginDTO);

        if ($User === null)
            return false;

        if ($User->status !== Status::Active)
            return false;

        if (session_status() !== PHP_SESSION_ACTIVE)
            session_start();

        session_regenerate_id(true);

        $_SESSION["UserID"] = $User->userId;
        $_SESSION["UserName"] = $User->username;
        $_SESSION["Email"] = $User->email;
        $_SESSION["PhoneNumber"] = $User->PhoneNumber;
        $_SESSION["Role"] = $User->role->value;
        $_SESSION["status"] = $User->status->value;

        return true;
    }

    public static function logout(): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE)
            session_start();

        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $Params = session_get_cookie_params();

            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $Params['path'],
                'domain' => $Params['domain'],
                'secure' => $Params['secure'],
                'httponly' => $Params['httponly'],
                'samesite' => $Params['samesite']
            ]);
        }

        session_destroy();

        return true;
    }
}