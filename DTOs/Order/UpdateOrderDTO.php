<?php
declare(strict_types=1);

use App\Enum\Order\OrderStatus;

final class UpdateOrderDTO
{
    public function __construct(
        public OrderStatus $orderStatus,
        public ?string $note,
        public string $shippingAddress,
        public string $city,
        public string $recipientPhone
    ) {
    }
}