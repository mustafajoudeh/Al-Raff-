<?php
declare(strict_types=1);

require_once __DIR__ . "/../../DTOs/Product/ProductDTO.php";

use Brick\Money\Money;

class ProductValidation
{
    public static function Validate(ProductDTO $DTO): array
    {
        $Errors = [];

        // Category
        if ($DTO->CategoryID <= 0) {
            $Errors[] = "Invalid category.";
        }

        // Product Name
        if (empty(trim($DTO->ProductName))) {
            $Errors[] = "Product name is required.";
        } elseif (strlen(trim($DTO->ProductName)) > 100) {
            $Errors[] = "Product name must not exceed 100 characters.";
        }

        // Price
        try {
            if (
                !preg_match('/^\d+(?:\.\d{1,3})?$/D', $DTO->Price)
            ) {
                $Errors[] = "Price must be a valid amount with up to 3 decimal places.";
            } else {
                $Price = Money::of($DTO->Price, "JOD");

                if (!$Price->isPositive()) {
                    $Errors[] = "Price must be greater than zero.";
                }
            }
        } catch (\Throwable $e) {
            $Errors[] = "Invalid price.";
        }

        // Stock
        if ($DTO->Stock < 0) {
            $Errors[] = "Stock cannot be negative.";
        }

        // Author
        if (empty(trim($DTO->Author))) {
            $Errors[] = "Author is required.";
        } elseif (strlen(trim($DTO->Author)) > 50) {
            $Errors[] = "Author must not exceed 50 characters.";
        }

        // Pages
        if ($DTO->Pages <= 0) {
            $Errors[] = "Pages must be greater than zero.";
        }

        // Description
        if (
            $DTO->Description !== null &&
            strlen($DTO->Description) > 255
        ) {
            $Errors[] = "Description must not exceed 255 characters.";
        }

        // ISBN
        if (
            $DTO->ISBN !== null &&
            strlen($DTO->ISBN) > 13
        ) {
            $Errors[] = "ISBN must not exceed 13 characters.";
        }

        // Publisher
        if (
            $DTO->Publisher !== null &&
            strlen($DTO->Publisher) > 50
        ) {
            $Errors[] = "Publisher must not exceed 50 characters.";
        }

        // Published Year
        if (
            $DTO->PublishedYear !== null &&
            $DTO->PublishedYear <= 0
        ) {
            $Errors[] = "Invalid published year.";
        }

        return $Errors;
    }
}