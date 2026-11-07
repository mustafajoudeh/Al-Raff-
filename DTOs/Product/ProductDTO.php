<?php

declare(strict_types=1);

class ProductDTO
{
    public function __construct(
        public ?int $ProductID,
        public int $CategoryID,
        public string $ProductName,
        public string $Price,
        public int $Stock,
        public string $Author,
        public int $Pages,
        public ?string $Description = null,
        public ?string $ISBN = null,
        public ?string $Publisher = null,
        public ?int $PublishedYear = null
    ) {
    }
}