<?php
declare(strict_types=1);

use App\Enum\Order\OrderStatus;

final class OrderResponseDTO
{
    public function __construct(
        public int $orderId,
        public int $userId,
        public OrderStatus $orderStatus,
        public string $totalPrice,
        public ?string $note,
        public string $shippingAddress,
        public string $city,
        public string $recipientPhone,
        public string $createdAt,
        public ?string $updatedAt,
        public ?int $updatedBy
    ) {
    }
}