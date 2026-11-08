<?php
declare(strict_types=1);

require_once __DIR__ . "/../config/DataBase.php";
require_once __DIR__ . "/../DTOs/Order/CreateOrderDTO.php";
require_once __DIR__ . "/../DTOs/Order/UpdateOrderDTO.php";
require_once __DIR__ . "/../DTOs/Order/OrderResponseDTO.php";

use App\Enum\Order\OrderStatus;

class OrderDAL
{
    private static function MapOrder(array $Row): OrderResponseDTO
    {
        return new OrderResponseDTO(
            (int) $Row['OrderID'],
            (int) $Row['UserID'],
            OrderStatus::from($Row['OrderStatus']),
            (string) $Row['TotalPrice'],
            $Row['Note'],
            $Row['ShippingAddress'],
            $Row['City'],
            $Row['RecipientPhone'],
            $Row['CreatedAt'],
            $Row['UpdatedAt'],
            $Row['UpdatedBy'] === null
            ? null
            : (int) $Row['UpdatedBy']
        );
    }

    public static function GetAllOrders(): ?array
    {
        try {
            $pdo = DataBase::Connect();

            $sql = "SELECT *
                    FROM `orders`
                    ORDER BY `CreatedAt` DESC, `OrderID` DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute();

            $Rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $Orders = [];

            foreach ($Rows as $Row) {
                $Orders[] = self::MapOrder($Row);
            }

            return $Orders;

        } catch (PDOException $e) {
            error_log($e->getMessage());
            return null;
        } finally {
            $pdo = null;
        }
    }

    public static function GetOrdersByUserID(int $UserID): ?array
    {
        try {
            $pdo = DataBase::Connect();

            $sql = "SELECT *
                    FROM `orders`
                    WHERE `UserID` = :UserID
                    ORDER BY `CreatedAt` DESC, `OrderID` DESC";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                'UserID' => $UserID
            ]);

            $Rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $Orders = [];

            foreach ($Rows as $Row) {
                $Orders[] = self::MapOrder($Row);
            }

            return $Orders;

        } catch (PDOException $e) {
            error_log($e->getMessage());
            return null;
        } finally {
            $pdo = null;
        }
    }

    public static function GetOrderByID(int $OrderID): ?OrderResponseDTO
    {
        try {
            $pdo = DataBase::Connect();

            $sql = "SELECT *
                    FROM `orders`
                    WHERE `OrderID` = :OrderID
                    LIMIT 1";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                'OrderID' => $OrderID
            ]);

            $Row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$Row) {
                return null;
            }

            return self::MapOrder($Row);

        } catch (PDOException $e) {
            error_log($e->getMessage());
            return null;
        } finally {
            $pdo = null;
        }
    }

    public static function AddOrder(CreateOrderDTO $DTO): int
    {
        try {
            $pdo = DataBase::Connect();

            $sql = "INSERT INTO `orders`
                    (
                        `UserID`,
                        `OrderStatus`,
                        `TotalPrice`,
                        `Note`,
                        `ShippingAddress`,
                        `City`,
                        `RecipientPhone`
                    )
                    VALUES
                    (
                        :UserID,
                        :OrderStatus,
                        :TotalPrice,
                        :Note,
                        :ShippingAddress,
                        :City,
                        :RecipientPhone
                    )";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                'UserID' => $DTO->userId,
                'OrderStatus' => $DTO->orderStatus->value,
                'TotalPrice' => $DTO->totalPrice,
                'Note' => $DTO->note,
                'ShippingAddress' => $DTO->shippingAddress,
                'City' => $DTO->city,
                'RecipientPhone' => $DTO->recipientPhone
            ]);

            return (int) $pdo->lastInsertId();

        } catch (PDOException $e) {
            error_log($e->getMessage());
            return 0;
        } finally {
            $pdo = null;
        }
    }

    public static function UpdateOrder(
        int $OrderID,
        UpdateOrderDTO $DTO,
        int $UpdatedBy
    ): bool {
        try {
            $pdo = DataBase::Connect();

            $sql = "UPDATE `orders`
                    SET
                        `OrderStatus` = :OrderStatus,
                        `Note` = :Note,
                        `ShippingAddress` = :ShippingAddress,
                        `City` = :City,
                        `RecipientPhone` = :RecipientPhone,
                        `UpdatedAt` = CURRENT_TIMESTAMP,
                        `UpdatedBy` = :UpdatedBy
                    WHERE `OrderID` = :OrderID";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                'OrderStatus' => $DTO->orderStatus->value,
                'Note' => $DTO->note,
                'ShippingAddress' => $DTO->shippingAddress,
                'City' => $DTO->city,
                'RecipientPhone' => $DTO->recipientPhone,
                'UpdatedBy' => $UpdatedBy,
                'OrderID' => $OrderID
            ]);

            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        } finally {
            $pdo = null;
        }
    }

    public static function UpdateOrderStatus(
        int $OrderID,
        OrderStatus $Status,
        int $UpdatedBy
    ): bool {
        try {
            $pdo = DataBase::Connect();

            $sql = "UPDATE `orders`
                    SET
                        `OrderStatus` = :Status,
                        `UpdatedAt` = CURRENT_TIMESTAMP,
                        `UpdatedBy` = :UpdatedBy
                    WHERE `OrderID` = :OrderID";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                'Status' => $Status->value,
                'UpdatedBy' => $UpdatedBy,
                'OrderID' => $OrderID
            ]);

            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        } finally {
            $pdo = null;
        }
    }

    public static function CancelOrder(
        int $OrderID,
        int $UserID
    ): bool {
        try {
            $pdo = DataBase::Connect();

            $sql = "UPDATE `orders`
                    SET
                        `OrderStatus` = :Status,
                        `UpdatedAt` = CURRENT_TIMESTAMP,
                        `UpdatedBy` = :UpdatedBy
                    WHERE `OrderID` = :OrderID
                    AND `UserID` = :UserID
                    AND `OrderStatus` = :Pending";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                'Status' => OrderStatus::Cancelled->value,
                'UpdatedBy' => $UserID,
                'OrderID' => $OrderID,
                'UserID' => $UserID,
                'Pending' => OrderStatus::Pending->value
            ]);

            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        } finally {
            $pdo = null;
        }
    }

    public static function IsOrderExisted(int $OrderID): bool
    {
        try {
            $pdo = DataBase::Connect();

            $sql = "SELECT 1
                    FROM `orders`
                    WHERE `OrderID` = :OrderID
                    LIMIT 1";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                'OrderID' => $OrderID
            ]);

            return $stmt->fetchColumn() !== false;

        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        } finally {
            $pdo = null;
        }
    }
}