<?php
declare(strict_types=1);

final class CategoryDTO
{
    public function __construct(
        public readonly ?int $CategoryID,
        public readonly string $CategoryName,
        public readonly ?string $Description,
        public readonly ?string $URLImage
    ) {
    }
}