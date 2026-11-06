<?php
declare(strict_types=1);

namespace App\Enum\User;
enum Role: string
{
    case Admin = "Admin";
    case Customer = "Customer";
}