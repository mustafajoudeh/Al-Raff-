<?php
declare(strict_types=1);

require_once __DIR__ . "/../../BAL/Category/Category.php";

final class CategoryController
{
    public static function GetAllCategories(Request $request): void
    {
        $Categories = Category::GetAllCategories();

        if ($Categories === null) {
            Response::json(500, false, 'Could not load categories');
        }

        Response::json(200, true, 'OK', $Categories);
    }

    public static function GetCategory(Request $request): void
    {
        $CategoryID = self::CategoryId($request);

        $Category = Category::GetDTOByID($CategoryID);

        if ($Category === null) {
            Response::json(404, false, 'Category not found');
        }

        Response::json(200, true, 'OK', $Category);
    }

    public static function AddCategory(Request $request): void
    {
        $body = $request->getBody();

        $DTO = new CategoryDTO(
            CategoryID: null,
            CategoryName: trim(self::stringValue($body, 'categoryName')),
            Description: self::nullableString($body, 'description'),
            URLImage: self::nullableString($body, 'urlImage')
        );

        $Category = new Category($DTO);

        if (!$Category->Save()) {
            self::saveFailed(
                $Category->GetErrorMessages(),
                'Could not create category'
            );
        }

        Response::json(201, true, 'Category created successfully', [
            'categoryId' => $Category->GetCategoryID()
        ]);
    }

    public static function UpdateCategory(Request $request): void
    {
        $CategoryID = self::CategoryId($request);

        $Current = Category::GetDTOByID($CategoryID);

        if ($Current === null) {
            Response::json(404, false, 'Category not found');
        }

        $body = $request->getBody();

        $DTO = new CategoryDTO(
            CategoryID: $CategoryID,
            CategoryName: trim(self::stringValue(
                $body,
                'categoryName',
                $Current->CategoryName
            )),
            Description: self::nullableString(
                $body,
                'description',
                $Current->Description
            ),
            URLImage: self::nullableString(
                $body,
                'urlImage',
                $Current->URLImage
            )
        );

        $Category = new Category($DTO);

        if (!$Category->Save()) {
            self::saveFailed(
                $Category->GetErrorMessages(),
                'Could not update category'
            );
        }

        Response::json(200, true, 'Category updated successfully');
    }

    public static function DeleteCategory(Request $request): void
    {
        $CategoryID = self::CategoryId($request);

        if (Category::GetDTOByID($CategoryID) === null) {
            Response::json(404, false, 'Category not found');
        }

        $HasProducts = Category::HasProducts($CategoryID);

        if ($HasProducts === null) {
            Response::json(500, false, 'Could not verify category products');
        }

        if ($HasProducts) {
            Response::json(
                409,
                false,
                'Cannot delete category because it contains products'
            );
        }

        if (!Category::DeleteCategory($CategoryID)) {
            Response::json(500, false, 'Could not delete category');
        }

        Response::json(200, true, 'Category deleted successfully');
    }

    private static function CategoryId(Request $request): int
    {
        $id = $request->getParam('id');

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

        if ($value === null) {
            return null;
        }

        return is_string($value) ? trim($value) : '';
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