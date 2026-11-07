<?php
declare(strict_types=1);

use App\Enum\User\Role;

final class AuthMiddleware
{
    public static function Authenticated(): callable
    {
        return function (Request $request): void {
            if (empty($_SESSION['UserID'])) {
                Response::json(401, false, 'Unauthenticated');
            }
        };
    }

    public static function RoleIs(Role $role): callable
    {
        return function (Request $request) use ($role): void {
            if (empty($_SESSION['UserID'])) {
                Response::json(401, false, 'Unauthenticated');
            }
            if (($_SESSION['Role'] ?? null) !== $role->value) {
                Response::json(403, false, 'Forbidden');
            }
        };
    }
}