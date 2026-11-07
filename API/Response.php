<?php
declare(strict_types=1);

class Response
{
    public static function json(
        int $statusCode,
        bool $success,
        string $message,
        mixed $data = null,
        array $errors = []
    ): void {

        http_response_code($statusCode);

        header('Content-Type: application/json; charset=utf-8');

        $response = [
            'success' => $success,
            'message' => $message,
            'data' => $data,
            'errors' => $errors
        ];

        echo json_encode(
            $response,
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES |
            JSON_INVALID_UTF8_SUBSTITUTE
        );

        exit;
    }
}