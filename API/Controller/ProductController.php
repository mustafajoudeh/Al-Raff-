<?php
declare(strict_types=1);

require_once __DIR__ . "/../../BAL/Product/Product.php";
require_once __DIR__ . "/../../BAL/Category/Category.php";

final class ProductController
{
    public static function GetAllProducts(Request $request): void
    {
        $Products = Product::GetAllProducts();

        if ($Products === null) {
            Response::json(500, false, 'Could not load products');
        }

        Response::json(200, true, 'OK', $Products);
    }

    public static function GetProduct(Request $request): void
    {
        $ProductID = self::ProductId($request);

        $Product = Product::GetProductDTO($ProductID);

        if ($Product === null) {
            Response::json(404, false, 'Product not found');
        }

        Response::json(200, true, 'OK', $Product);
    }

    public static function GetProductsByCategory(Request $request): void
    {
        $CategoryID = self::CategoryId($request);

        if (!Category::IsCategoryExisted($CategoryID)) {
            Response::json(404, false, 'Category not found');
        }

        $Products = Product::GetProductsByCategoryID($CategoryID);

        if ($Products === null) {
            Response::json(500, false, 'Could not load products');
        }

        Response::json(200, true, 'OK', $Products);
    }

    public static function AddProduct(Request $request): void
    {
        $body = $request->getBody();

        $DTO = self::CreateDTO($body);

        $Product = new Product($DTO);

        if (!$Product->Save()) {
            self::saveFailed(
                $Product->GetErrorMessages(),
                'Could not create product'
            );
        }

        Response::json(201, true, 'Product created successfully', [
            'productId' => $Product->GetProductID()
        ]);
    }

    public static function UpdateProduct(Request $request): void
    {
        $ProductID = self::ProductId($request);

        $Product = Product::GetProductByID($ProductID);

        if ($Product === null) {
            Response::json(404, false, 'Product not found');
        }

        $DTO = self::CreateDTO(
            $request->getBody(),
            $ProductID,
            $Product
        );

        $Product->Fill($DTO);

        if (!$Product->Save()) {
            self::saveFailed(
                $Product->GetErrorMessages(),
                'Could not update product'
            );
        }

        Response::json(200, true, 'Product updated successfully');
    }

    public static function DeleteProduct(Request $request): void
    {
        $ProductID = self::ProductId($request);

        if (Product::GetProductDTO($ProductID) === null) {
            Response::json(404, false, 'Product not found');
        }

        if (!Product::DeleteProduct($ProductID)) {
            Response::json(500, false, 'Could not delete product');
        }

        Response::json(200, true, 'Product deleted successfully');
    }

    private static function CreateDTO(
        array $body,
        ?int $ProductID = null,
        ?Product $Current = null
    ): ProductDTO {
        return new ProductDTO(
            ProductID: $ProductID,

            CategoryID: self::intValue(
                $body,
                'categoryId',
                $Current?->CategoryID ?? 0
            ),

            ProductName: trim(self::stringValue(
                $body,
                'productName',
                $Current?->ProductName ?? ''
            )),

            Price: self::stringValue(
                $body,
                'price',
                $Current?->Price ?? ''
            ),

            Stock: self::intValue(
                $body,
                'stock',
                $Current?->Stock ?? 0
            ),

            Author: trim(self::stringValue(
                $body,
                'author',
                $Current?->Author ?? ''
            )),

            Pages: self::intValue(
                $body,
                'pages',
                $Current?->Pages ?? 0
            ),

            Description: self::nullableString(
                $body,
                'description',
                $Current?->Description ?? null
            ),

            ISBN: self::nullableString(
                $body,
                'isbn',
                $Current?->ISBN ?? null
            ),

            Publisher: self::nullableString(
                $body,
                'publisher',
                $Current?->Publisher ?? null
            ),

            PublishedYear: self::nullableInt(
                $body,
                'publishedYear',
                $Current?->PublishedYear ?? null
            )
        );
    }

    private static function ProductId(Request $request): int
    {
        $id = $request->getParam('id');

        if ($id === null || !ctype_digit($id) || (int) $id <= 0) {
            Response::json(400, false, 'Invalid product id');
        }

        return (int) $id;
    }

    private static function CategoryId(Request $request): int
    {
        $id = $request->getParam('categoryId');

        if ($id === null || !ctype_digit($id) || (int) $id <= 0) {
            Response::json(400, false, 'Invalid category id');
        }

        return (int) $id;
    }

    private static function stringValue(
        array $body,
        string $key,
        string $default = ''
    ): string {
        $value = $body[$key] ?? $default;

        return is_string($value) ? $value : '';
    }

    private static function nullableString(
        array $body,
        string $key,
        ?string $default = null
    ): ?string {
        if (!array_key_exists($key, $body)) {
            return $default;
        }

        $value = $body[$key];

        if ($value === null || $value === '') {
            return null;
        }

        return is_string($value) ? trim($value) : '';
    }

    private static function intValue(
        array $body,
        string $key,
        int $default = 0
    ): int {
        if (!array_key_exists($key, $body)) {
            return $default;
        }

        $value = $body[$key];

        if (is_int($value)) {
            return $value;
        }

        if (
            is_string($value) && filter_var(
                $value,
                FILTER_VALIDATE_INT
            ) !== false
        ) {
            return (int) $value;
        }

        return -1;
    }

    private static function nullableInt(
        array $body,
        string $key,
        ?int $default = null
    ): ?int {
        if (!array_key_exists($key, $body)) {
            return $default;
        }

        $value = $body[$key];

        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        if (
            is_string($value) && filter_var(
                $value,
                FILTER_VALIDATE_INT
            ) !== false
        ) {
            return (int) $value;
        }

        return 0;
    }

    private static function saveFailed(
        array $errors,
        string $message
    ): void {
        if ($errors) {
            Response::json(422, false, 'Validation failed', null, $errors);
        }

        Response::json(500, false, $message);
    }
}