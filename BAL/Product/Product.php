<?php
declare(strict_types=1);

require_once __DIR__ . "/../../DAL/ProductDAL.php";
require_once __DIR__ . "/ProductValidation.php";
require_once __DIR__ . "/../../Enum/Mode.php";

use App\Enum\en_Mode;
use Brick\Money\Money;

class Product
{
    public ?int $ProductID;
    public int $CategoryID;
    public string $ProductName;
    public string $Price;
    public int $Stock;
    public string $Author;
    public int $Pages;
    public ?string $Description;
    public ?string $ISBN;
    public ?string $Publisher;
    public ?int $PublishedYear;

    private en_Mode $Mode;

    private array $ErrorMessages = [];

    public function __construct(ProductDTO $DTO)
    {
        $this->ProductID = $DTO->ProductID;
        $this->CategoryID = $DTO->CategoryID;
        $this->ProductName = $DTO->ProductName;
        $this->Price = $DTO->Price;
        $this->Stock = $DTO->Stock;
        $this->Author = $DTO->Author;
        $this->Pages = $DTO->Pages;
        $this->Description = $DTO->Description;
        $this->ISBN = $DTO->ISBN;
        $this->Publisher = $DTO->Publisher;
        $this->PublishedYear = $DTO->PublishedYear;

        $this->Mode = $DTO->ProductID === null
            ? en_Mode::Add
            : en_Mode::Update;
    }

    private function _GetDTO(): ProductDTO
    {
        return new ProductDTO(
            $this->ProductID,
            $this->CategoryID,
            $this->ProductName,
            $this->Price,
            $this->Stock,
            $this->Author,
            $this->Pages,
            $this->Description,
            $this->ISBN,
            $this->Publisher,
            $this->PublishedYear
        );
    }

    public function GetErrorMessages(): array
    {
        return $this->ErrorMessages;
    }

    public function GetProductID(): ?int
    {
        return $this->ProductID;
    }

    public function GetProductName(): string
    {
        return $this->ProductName;
    }

    public function GetPrice(): Money
    {
        return Money::of($this->Price, "JOD");
    }

    public function GetStock(): int
    {
        return $this->Stock;
    }

    public static function GetProductDTO(int $ProductID): ?ProductDTO
    {
        if ($ProductID <= 0)
            return null;

        return ProductDAL::GetProduct($ProductID);
    }

    public function Fill(ProductDTO $DTO): void
    {
        $this->CategoryID = $DTO->CategoryID;
        $this->ProductName = $DTO->ProductName;
        $this->Price = $DTO->Price;
        $this->Stock = $DTO->Stock;
        $this->Author = $DTO->Author;
        $this->Pages = $DTO->Pages;
        $this->Description = $DTO->Description;
        $this->ISBN = $DTO->ISBN;
        $this->Publisher = $DTO->Publisher;
        $this->PublishedYear = $DTO->PublishedYear;

        $this->ErrorMessages = [];
    }

    private function _AddNewProduct(): bool
    {
        $this->ErrorMessages = ProductValidation::Validate(
            $this->_GetDTO()
        );

        if (!empty($this->ErrorMessages)) {
            return false;
        }

        $NewID = ProductDAL::AddProduct($this->_GetDTO());

        if ($NewID <= 0) {
            $this->ErrorMessages[] = "Failed to add product.";
            return false;
        }

        $this->ProductID = $NewID;
        $this->Mode = en_Mode::Update;

        return true;
    }

    private function _UpdateProduct(): bool
    {
        if ($this->ProductID === null || $this->ProductID <= 0) {
            $this->ErrorMessages[] = "Invalid product ID.";
            return false;
        }

        $this->ErrorMessages = ProductValidation::Validate(
            $this->_GetDTO()
        );

        if (!empty($this->ErrorMessages)) {
            return false;
        }

        if (!ProductDAL::UpdateProduct($this->_GetDTO())) {
            $this->ErrorMessages[] = "Failed to update product.";
            return false;
        }

        return true;
    }

    public function Save(): bool
    {
        return match ($this->Mode) {
            en_Mode::Add => $this->_AddNewProduct(),
            en_Mode::Update => $this->_UpdateProduct()
        };
    }

    public function CalculateTotal(int $Quantity): ?Money
    {
        if ($Quantity <= 0) {
            return null;
        }

        return $this->GetPrice()->multipliedBy($Quantity);
    }

    public static function GetProductByID(int $ProductID): ?self
    {
        $DTO = ProductDAL::GetProduct($ProductID);

        if ($DTO === null) {
            return null;
        }

        return new self($DTO);
    }

    public static function GetAllProducts(): ?array
    {
        return ProductDAL::GetAllProducts();
    }

    public static function GetProductsByCategoryID(int $CategoryID): ?array
    {
        return ProductDAL::GetProductsByCategoryID($CategoryID);
    }

    public static function DeleteProduct(int $ProductID): bool
    {
        if ($ProductID <= 0) {
            return false;
        }

        return ProductDAL::DeleteProduct($ProductID);
    }
}