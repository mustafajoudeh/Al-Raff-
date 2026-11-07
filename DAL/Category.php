<?php
declare(strict_types=1);

require_once __DIR__ . "/../config/DataBase.php";
require_once __DIR__ . "/../DTOs/Category/CategoryDTO.php";

class CategoryDAL
{
    public static function GetAllCategories(): ?array
    {
        try {
            $pdo = DataBase::Connect();

            $sql = "SELECT `CategoryName`, `Description`, `URLImage`
                    FROM `category`";

            $stmt = $pdo->prepare($sql);
            $stmt->execute();

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $Categories = [];

            foreach ($rows as $row) {
                $Categories[] = new CategoryDTO(
                    null,
                    $row["CategoryName"],
                    $row["Description"],
                    $row["URLImage"]
                );
            }

            return $Categories;

        } catch (PDOException $e) {
            error_log($e->getMessage());
            return null;
        } finally {
            $pdo = null;
        }
    }

    public static function GetCategory(int $CategoryID): ?CategoryDTO
    {
        try {
            $pdo = DataBase::Connect();

            $sql = "SELECT *
                    FROM `category`
                    WHERE `CategoryID` = :CategoryID";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                "CategoryID" => $CategoryID
            ]);

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row)
                return null;

            return new CategoryDTO(
                (int) $row["CategoryID"],
                $row["CategoryName"],
                $row["Description"],
                $row["URLImage"]
            );

        } catch (PDOException $e) {
            error_log($e->getMessage());
            return null;
        } finally {
            $pdo = null;
        }
    }

    public static function AddCategory(CategoryDTO $DTO): int
    {
        try {
            $pdo = DataBase::Connect();

            $sql = "INSERT INTO `category`
                    (`CategoryName`, `Description`, `URLImage`)
                    VALUES
                    (:CategoryName, :Description, :URLImage)";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                "CategoryName" => $DTO->CategoryName,
                "Description" => $DTO->Description,
                "URLImage" => $DTO->URLImage
            ]);

            return (int) $pdo->lastInsertId();

        } catch (PDOException $e) {
            error_log($e->getMessage());
            return 0;
        } finally {
            $pdo = null;
        }
    }

    public static function UpdateCategory(CategoryDTO $DTO): bool
    {
        try {
            if ($DTO->CategoryID === null || $DTO->CategoryID <= 0)
                return false;

            $pdo = DataBase::Connect();

            $sql = "UPDATE `category`
                    SET
                        `CategoryName` = :CategoryName,
                        `Description` = :Description,
                        `URLImage` = :URLImage
                    WHERE `CategoryID` = :CategoryID";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                "CategoryID" => $DTO->CategoryID,
                "CategoryName" => $DTO->CategoryName,
                "Description" => $DTO->Description,
                "URLImage" => $DTO->URLImage
            ]);

            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        } finally {
            $pdo = null;
        }
    }

    public static function DeleteCategory(int $CategoryID): bool
    {
        try {
            if ($CategoryID <= 0)
                return false;

            $pdo = DataBase::Connect();

            $sql = "DELETE FROM `category`
                    WHERE `CategoryID` = :CategoryID";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                "CategoryID" => $CategoryID
            ]);

            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        } finally {
            $pdo = null;
        }
    }

    public static function IsCategoryNameExists(
        string $CategoryName,
        ?int $CategoryID = null
    ): bool {
        try {
            $pdo = DataBase::Connect();

            $sql = "SELECT EXISTS(
                        SELECT 1
                        FROM `category`
                        WHERE `CategoryName` = :CategoryName";

            if ($CategoryID !== null)
                $sql .= " AND `CategoryID` != :CategoryID";

            $sql .= ")";

            $stmt = $pdo->prepare($sql);

            $params = [
                "CategoryName" => $CategoryName
            ];

            if ($CategoryID !== null)
                $params["CategoryID"] = $CategoryID;

            $stmt->execute($params);

            return (bool) $stmt->fetchColumn();

        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        } finally {
            $pdo = null;
        }
    }
}