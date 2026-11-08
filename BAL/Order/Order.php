<?php
declare(strict_types=1);

require_once __DIR__ . "/../../DAL/OrderDAL.php";
require_once __DIR__ . "/../../DTOs/Order/CreateOrderDTO.php";
require_once __DIR__ . "/../../DTOs/Order/UpdateOrderDTO.php";
require_once __DIR__ . "/../../DTOs/Order/OrderResponseDTO.php";
require_once __DIR__ . "/OrderValidation.php";
require_once __DIR__ . "/../../Enum/Mode.php";

use App\Enum\en_Mode;
use App\Enum\Order\OrderStatus;
use Brick\Money\Money;

class Order
{
    public ?int $OrderID;
    public int $UserID;
    public OrderStatus $OrderStatus;
    public string $TotalPrice;
    public ?string $Note;
    public string $ShippingAddress;
    public string $City;
    public string $RecipientPhone;

    public ?string $CreatedAt;
    public ?string $UpdatedAt;
    public ?int $UpdatedBy;

    private en_Mode $Mode;

    private array $ErrorMessages = [];

    public function __construct(array|CreateOrderDTO|OrderResponseDTO $DTO)
    {
        if ($DTO instanceof CreateOrderDTO) {
            $this->OrderID = null;
            $this->CreatedAt = null;
            $this->UpdatedAt = null;
            $this->UpdatedBy = null;
        } else {
            $this->OrderID = $DTO->orderId;
            $this->CreatedAt = $DTO->createdAt;
            $this->UpdatedAt = $DTO->updatedAt;
            $this->UpdatedBy = $DTO->updatedBy;
        }

        $this->UserID = $DTO->userId;
        $this->OrderStatus = $DTO->orderStatus;
        $this->TotalPrice = $DTO->totalPrice;
        $this->Note = $DTO->note;
        $this->ShippingAddress = $DTO->shippingAddress;
        $this->City = $DTO->city;
        $this->RecipientPhone = $DTO->recipientPhone;

        $this->Mode = $this->OrderID === null
            ? en_Mode::Add
            : en_Mode::Update;
    }

    private function _GetCreateDTO(): CreateOrderDTO
    {
        return new CreateOrderDTO(
            $this->UserID,
            $this->OrderStatus,
            $this->TotalPrice,
            $this->Note,
            $this->ShippingAddress,
            $this->City,
            $this->RecipientPhone
        );
    }

    private function _GetUpdateDTO(): UpdateOrderDTO
    {
        return new UpdateOrderDTO(
            $this->OrderStatus,
            $this->Note,
            $this->ShippingAddress,
            $this->City,
            $this->RecipientPhone
        );
    }

    public function GetErrorMessages(): array
    {
        return $this->ErrorMessages;
    }

    public function GetOrderID(): ?int
    {
        return $this->OrderID;
    }

    public function GetUserID(): int
    {
        return $this->UserID;
    }

    public function GetOrderStatus(): OrderStatus
    {
        return $this->OrderStatus;
    }

    public function GetTotalPrice(): Money
    {
        return Money::of($this->TotalPrice, "JOD");
    }

    public function GetNote(): ?string
    {
        return $this->Note;
    }

    public function GetShippingAddress(): string
    {
        return $this->ShippingAddress;
    }

    public function GetCity(): string
    {
        return $this->City;
    }

    public function GetRecipientPhone(): string
    {
        return $this->RecipientPhone;
    }

    public function Fill(UpdateOrderDTO $DTO): void
    {
        $this->OrderStatus = $DTO->orderStatus;
        $this->Note = $DTO->note;
        $this->ShippingAddress = $DTO->shippingAddress;
        $this->City = $DTO->city;
        $this->RecipientPhone = $DTO->recipientPhone;

        $this->ErrorMessages = [];
    }

    private function _AddNewOrder(): bool
    {
        $this->ErrorMessages = OrderValidation::ValidateCreate(
            $this->_GetCreateDTO()
        );

        if (!empty($this->ErrorMessages)) {
            return false;
        }

        $NewID = OrderDAL::AddOrder($this->_GetCreateDTO());

        if ($NewID <= 0) {
            $this->ErrorMessages[] = "Failed to create order.";
            return false;
        }

        $this->OrderID = $NewID;
        $this->Mode = en_Mode::Update;

        return true;
    }

    private function _UpdateOrder(int $UpdatedBy): bool
    {
        if ($this->OrderID === null || $this->OrderID <= 0) {
            $this->ErrorMessages[] = "Invalid order ID.";
            return false;
        }

        if ($UpdatedBy <= 0) {
            $this->ErrorMessages[] = "Invalid user ID.";
            return false;
        }

        $this->ErrorMessages = OrderValidation::ValidateUpdate(
            $this->_GetUpdateDTO()
        );

        if (!empty($this->ErrorMessages)) {
            return false;
        }

        if (
            !OrderDAL::UpdateOrder(
                $this->OrderID,
                $this->_GetUpdateDTO(),
                $UpdatedBy
            )
        ) {
            $this->ErrorMessages[] = "Failed to update order.";
            return false;
        }

        $this->UpdatedBy = $UpdatedBy;

        return true;
    }

    public function Save(?int $UpdatedBy = null): bool
    {
        return match ($this->Mode) {
            en_Mode::Add => $this->_AddNewOrder(),
            en_Mode::Update => $UpdatedBy !== null
                ? $this->_UpdateOrder($UpdatedBy)
                : false
        };
    }

    public static function GetOrderDTO(int $OrderID): ?OrderResponseDTO
    {
        if ($OrderID <= 0) {
            return null;
        }

        return OrderDAL::GetOrderByID($OrderID);
    }

    public static function GetOrderByID(int $OrderID): ?self
    {
        $DTO = self::GetOrderDTO($OrderID);

        if ($DTO === null) {
            return null;
        }

        return new self($DTO);
    }

    public static function GetOrderByIDForUser(
        int $UserID
    ): ?self {
        if ($UserID <= 0) {
            return null;
        }

        $DTO = OrderDAL::GetOrdersByUserID($UserID);

        if ($DTO === null) {
            return null;
        }

        return new self($DTO);
    }

    public static function GetAllOrders(): ?array
    {
        return OrderDAL::GetAllOrders();
    }

    public static function GetOrdersByUserID(int $UserID): ?array
    {
        if ($UserID <= 0) {
            return null;
        }

        return OrderDAL::GetOrdersByUserID($UserID);
    }

    public static function CancelOrder(int $OrderID, int $UserID): bool
    {
        if ($OrderID <= 0 || $UserID <= 0) {
            return false;
        }

        return OrderDAL::CancelOrder($OrderID, $UserID);
    }
}