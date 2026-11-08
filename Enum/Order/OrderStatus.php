<?php
declare(strict_types=1);

namespace App\Enum\Order;

enum OrderStatus: string
{
    case Pending = 'Pending';
    case Processing = 'Processing';
    case Shipped = 'Shipped';
    case Delivered = 'Delivered';
    case Cancelled = 'Cancelled';
    case Returned = 'Returned';
}