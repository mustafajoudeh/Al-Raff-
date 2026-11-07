<?php
declare(strict_types=1);

final class ProductListDTO
{
    public function __construct(
        public int $ProductID,
        public string $ProductName,
        public string $CategoryName,
        public string $Price,
        public int $Stock,
        public string $Author
    ) {
    }
}