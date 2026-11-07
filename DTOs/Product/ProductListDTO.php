<?php
declare(strict_types=1);

class ProductListDTO
{
    public function __construct(
        public string $ProductName,
        public string $CategoryName,
        public string $Price,
        public int $Stock,
        public string $Author
    ) {
    }
}