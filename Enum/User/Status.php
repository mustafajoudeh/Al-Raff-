<?php
declare(strict_types=1);

namespace App\Enum\User;

enum Status: string
{
    case Active = "Active";
    case InActive = "InActive";
}