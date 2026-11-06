<?php
declare(strict_types=1);

final class LoginDTO
{
    public function __construct(
        public readonly string $Email,
        public readonly string $Password
    ) {
    }
}