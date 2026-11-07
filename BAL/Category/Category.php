<?php
declare(strict_types=1);

require_once __DIR__ . "/../../DAL/CategoryDAL.php";
require_once __DIR__ . "/../../DTOs/Category/CategoryDTO.php";
require_once __DIR__ . "/../../DAL/ProductDAL.php";

use App\Enum\en_Mode;

class Category
{
    private ?int $categoryID;
    private string $categoryName;
    private ?string $description;
    private ?string $urlImage;

    private en_Mode $Mode;

    private array $ErrorMessages = [];

    private function _GetCategoryDTO(): CategoryDTO
    {
        return new CategoryDTO(
            CategoryID: $this->categoryID,
            CategoryName: $this->categoryName,
            Description: $this->description,
            URLImage: $this->urlImage
        );
    }

    public function GetErrorMessages(): array
    {
        return $this->ErrorMessages;
    }

    public function GetCategoryID(): ?int
    {
        return $this->categoryID;
    }

    public function Fill(CategoryDTO $data): void
    {
        $this->categoryName = $data->CategoryName;
        $this->description = $data->Description;
        $this->urlImage = $data->URLImage;
    }

    public function __construct(CategoryDTO $data)
    {
        $this->categoryID = $data->CategoryID;
        $this->categoryName = $data->CategoryName;
        $this->description = $data->Description;
        $this->urlImage = $data->URLImage;

        $this->Mode = $data->CategoryID === null
            ? en_Mode::Add
            : en_Mode::Update;
    }

    public static function GetDTOByID(int $CategoryID): ?CategoryDTO
    {
        if ($CategoryID <= 0)
            return null;

        return CategoryDAL::GetCategory($CategoryID);
    }

    public static function FindCategoryByID(int $CategoryID): ?Category
    {
        if ($CategoryID <= 0)
            return null;

        $CategoryDTO = CategoryDAL::GetCategory($CategoryID);

        if ($CategoryDTO === null)
            return null;

        return new Category($CategoryDTO);
    }

    public static function GetAllCategories(): ?array
    {
        return CategoryDAL::GetAllCategories();
    }

    private function _Validate(): bool
    {
        $this->ErrorMessages = [];

        if (empty(trim($this->categoryName))) {
            $this->ErrorMessages[] = "Category name is required.";
            return false;
        }

        if ($this->Mode === en_Mode::Add) {
            if (CategoryDAL::IsCategoryNameExists($this->categoryName)) {
                $this->ErrorMessages[] = "Category name already exists.";
                return false;
            }
        }

        if ($this->Mode === en_Mode::Update) {
            if (
                $this->categoryID === null ||
                $this->categoryID <= 0
            ) {
                $this->ErrorMessages[] = "Invalid category ID.";
                return false;
            }

            if (
                CategoryDAL::IsCategoryNameExists(
                    $this->categoryName,
                    $this->categoryID
                )
            ) {
                $this->ErrorMessages[] = "Category name already exists.";
                return false;
            }
        }

        return true;
    }

    private function _AddNewCategory(): bool
    {
        if (!$this->_Validate())
            return false;

        $DTO = $this->_GetCategoryDTO();

        $NewCategoryID = CategoryDAL::AddCategory($DTO);

        if ($NewCategoryID <= 0)
            return false;

        $this->categoryID = $NewCategoryID;
        $this->Mode = en_Mode::Update;

        return true;
    }

    private function _UpdateCategory(): bool
    {
        if (!$this->_Validate())
            return false;

        $DTO = $this->_GetCategoryDTO();

        return CategoryDAL::UpdateCategory($DTO);
    }

    public function Save(): bool
    {
        return match ($this->Mode) {

            en_Mode::Add => $this->_AddNewCategory(),

            en_Mode::Update => $this->_UpdateCategory()

        };
    }

    public static function DeleteCategory(int $CategoryID): bool
    {
        if ($CategoryID <= 0) {
            return false;
        }

        $HasProducts = self::HasProducts($CategoryID);

        if ($HasProducts !== false) {
            return false;
        }

        return CategoryDAL::DeleteCategory($CategoryID);
    }

    public static function IsCategoryExisted(int $CategoryID): bool
    {
        if ($CategoryID <= 0)
            return false;
        return CategoryDAL::IsCategoryExisted($CategoryID);
    }

    public static function HasProducts(int $CategoryID): ?bool
    {
        if ($CategoryID <= 0) {
            return false;
        }

        return ProductDAL::HasProductsInCategory($CategoryID);
    }

}