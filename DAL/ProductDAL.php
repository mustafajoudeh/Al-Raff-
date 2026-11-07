<?php
declare(strict_types=1);

require_once __DIR__ . "/../config/DataBase.php";
require_once __DIR__ . "/../DTOs/Product/ProductDTO.php";
require_once __DIR__ . "/../DTOs/Product/ProductListDTO.php";

class ProductDAL
{
    private static function MapRowToDTO(array $row): ProductDTO
    {
        return new ProductDTO(
            (int) $row["ProductID"],
            (int) $row["CategoryID"],
            $row["ProductName"],
            (string) $row["Price"],
            (int) $row["Stock"],
            $row["Author"],
            (int) $row["Pages"],
            $row["Description"],
            $row["ISBN"],
            $row["Publisher"],
            $row["PublishedYear"] !== null
            ? (int) $row["PublishedYear"]
            : null
        );
    }

    public static function GetAllProducts(): ?array
    {
        $pdo = null;

        try {
            $pdo = DataBase::Connect();

            $sql = "SELECT
                    p.ProductName,
                    c.CategoryName,
                    p.Price,
                    p.Stock,
                    p.Author
                FROM product AS p
                INNER JOIN category AS c
                    ON p.CategoryID = c.CategoryID";

            $stmt = $pdo->prepare($sql);
            $stmt->execute();

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $Products = [];

            foreach ($rows as $row) {
                $Products[] = new ProductListDTO(
                    $row["ProductName"],
                    $row["CategoryName"],
                    (string) $row["Price"],
                    (int) $row["Stock"],
                    $row["Author"]
                );
            }

            return $Products;

        } catch (PDOException $e) {
            error_log($e->getMessage());
            return null;
        } finally {
            $pdo = null;
        }
    }

    public static function GetProduct(int $ProductID): ?ProductDTO
    {
        $pdo = null;

        try {
            $pdo = DataBase::Connect();

            $sql = "SELECT * FROM `product`
                    WHERE `ProductID` = :ProductID";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                "ProductID" => $ProductID
            ]);

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                return null;
            }

            return self::MapRowToDTO($row);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return null;
        } finally {
            $pdo = null;
        }
    }

    public static function GetProductsByCategoryID(int $CategoryID): ?array
    {
        $pdo = null;

        try {
            $pdo = DataBase::Connect();

            $sql = "SELECT * FROM `product`
                    WHERE `CategoryID` = :CategoryID";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                "CategoryID" => $CategoryID
            ]);

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $Products = [];

            foreach ($rows as $row) {
                $Products[] = self::MapRowToDTO($row);
            }

            return $Products;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return null;
        } finally {
            $pdo = null;
        }
    }

    public static function AddProduct(ProductDTO $DTO): int
    {
        $pdo = null;

        try {
            $pdo = DataBase::Connect();

            $sql = "INSERT INTO `product`
                    (
                        `CategoryID`,
                        `ProductName`,
                        `Description`,
                        `Price`,
                        `Stock`,
                        `ISBN`,
                        `Author`,
                        `Publisher`,
                        `PublishedYear`,
                        `Pages`
                    )
                    VALUES
                    (
                        :CategoryID,
                        :ProductName,
                        :Description,
                        :Price,
                        :Stock,
                        :ISBN,
                        :Author,
                        :Publisher,
                        :PublishedYear,
                        :Pages
                    )";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                "CategoryID" => $DTO->CategoryID,
                "ProductName" => trim($DTO->ProductName),
                "Description" => $DTO->Description,
                "Price" => $DTO->Price,
                "Stock" => $DTO->Stock,
                "ISBN" => $DTO->ISBN,
                "Author" => trim($DTO->Author),
                "Publisher" => $DTO->Publisher,
                "PublishedYear" => $DTO->PublishedYear,
                "Pages" => $DTO->Pages
            ]);

            return (int) $pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return 0;
        } finally {
            $pdo = null;
        }
    }

    public static function UpdateProduct(ProductDTO $DTO): bool
    {
        if ($DTO->ProductID === null || $DTO->ProductID <= 0) {
            return false;
        }

        $pdo = null;

        try {
            $pdo = DataBase::Connect();

            $sql = "UPDATE `product` SET
                        `CategoryID` = :CategoryID,
                        `ProductName` = :ProductName,
                        `Description` = :Description,
                        `Price` = :Price,
                        `Stock` = :Stock,
                        `ISBN` = :ISBN,
                        `Author` = :Author,
                        `Publisher` = :Publisher,
                        `PublishedYear` = :PublishedYear,
                        `Pages` = :Pages
                    WHERE `ProductID` = :ProductID";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                "CategoryID" => $DTO->CategoryID,
                "ProductName" => trim($DTO->ProductName),
                "Description" => $DTO->Description,
                "Price" => $DTO->Price,
                "Stock" => $DTO->Stock,
                "ISBN" => $DTO->ISBN,
                "Author" => trim($DTO->Author),
                "Publisher" => $DTO->Publisher,
                "PublishedYear" => $DTO->PublishedYear,
                "Pages" => $DTO->Pages,
                "ProductID" => $DTO->ProductID
            ]);

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        } finally {
            $pdo = null;
        }
    }

    public static function DeleteProduct(int $ProductID): bool
    {
        $pdo = null;

        try {
            $pdo = DataBase::Connect();

            $sql = "DELETE FROM `product`
                    WHERE `ProductID` = :ProductID";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                "ProductID" => $ProductID
            ]);

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        } finally {
            $pdo = null;
        }
    }
}