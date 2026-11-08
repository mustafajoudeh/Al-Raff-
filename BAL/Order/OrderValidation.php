<?php
declare(strict_types=1);

require_once __DIR__ . "/../../DTOs/Order/CreateOrderDTO.php";
require_once __DIR__ . "/../../DTOs/Order/UpdateOrderDTO.php";
require_once __DIR__ . "/../../Enum/Order/OrderStatus.php";

use App\Enum\Order\OrderStatus;
use Brick\Money\Money;

final class OrderValidation
{
    public static function ValidateCreate(CreateOrderDTO $DTO): array
    {
        $Errors = [];

        if ($DTO->userId <= 0) {
            $Errors[] = "Invalid user ID.";
        }

        if (!self::IsValidPrice($DTO->totalPrice)) {
            $Errors[] = "Order total price must be a valid positive JOD amount.";
        }

        if (!self::IsValidNote($DTO->note)) {
            $Errors[] = "Note cannot exceed 255 characters.";
        }

        if (!self::IsValidShippingAddress($DTO->shippingAddress)) {
            $Errors[] = "Shipping address is required.";
        }

        if (!self::IsValidCity($DTO->city)) {
            $Errors[] = "City is required and cannot exceed 50 characters.";
        }

        if (!self::IsValidPhone($DTO->recipientPhone)) {
            $Errors[] = "Recipient phone number must contain exactly 10 digits.";
        }

        return $Errors;
    }

    public static function ValidateUpdate(UpdateOrderDTO $DTO): array
    {
        $Errors = [];

        if (!self::IsValidOrderStatus($DTO->orderStatus)) {
            $Errors[] = "Invalid order status.";
        }

        if (!self::IsValidNote($DTO->note)) {
            $Errors[] = "Note cannot exceed 255 characters.";
        }

        if (!self::IsValidShippingAddress($DTO->shippingAddress)) {
            $Errors[] = "Shipping address is required.";
        }

        if (!self::IsValidCity($DTO->city)) {
            $Errors[] = "City is required and cannot exceed 50 characters.";
        }

        if (!self::IsValidPhone($DTO->recipientPhone)) {
            $Errors[] = "Recipient phone number must contain exactly 10 digits.";
        }

        return $Errors;
    }

    private static function IsValidPrice(string $Price): bool
    {
        $Price = trim($Price);

        if ($Price === "") {
            return false;
        }

        try {
            return Money::of($Price, "JOD")->isPositive();
        } catch (Throwable $Exception) {
            return false;
        }
    }

    private static function IsValidOrderStatus(OrderStatus $Status): bool
    {
        return OrderStatus::tryFrom($Status->value) !== null;
    }

    private static function IsValidNote(?string $Note): bool
    {
        if ($Note === null) {
            return true;
        }

        return mb_strlen(trim($Note)) <= 255;
    }

    private static function IsValidShippingAddress(string $Address): bool
    {
        return trim($Address) !== "";
    }

    private static function IsValidCity(string $City): bool
    {
        $City = trim($City);

        return $City !== "" && mb_strlen($City) <= 50;
    }

    private static function IsValidPhone(string $Phone): bool
    {
        return preg_match('/^[0-9]{10}$/', trim($Phone)) === 1;
    }

    public static function IsValidStatusTransition(
        OrderStatus $CurrentStatus,
        OrderStatus $NewStatus
    ): bool {
        if ($CurrentStatus === $NewStatus) {
            return false;
        }

        $AllowedTransitions = [
            OrderStatus::Pending->value => [
                OrderStatus::Processing,
                OrderStatus::Cancelled,
            ],

            OrderStatus::Processing->value => [
                OrderStatus::Shipped,
                OrderStatus::Cancelled,
            ],

            OrderStatus::Shipped->value => [
                OrderStatus::Delivered,
            ],

            OrderStatus::Delivered->value => [
                OrderStatus::Returned,
            ],

            OrderStatus::Cancelled->value => [],

            OrderStatus::Returned->value => [],
        ];

        return in_array(
            $NewStatus,
            $AllowedTransitions[$CurrentStatus->value] ?? [],
            true
        );
    }
}