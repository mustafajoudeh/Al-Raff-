<?php
declare(strict_types=1);

class Request
{
    private string $method;
    private string $path;
    private array $body;

    private array $params = [];

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function getParam(string $key): ?string
    {
        return $this->params[$key] ?? null;
    }

    public function __construct(
        string $method,
        string $path,
        array $body = []
    ) {
        $this->method = strtoupper($method);
        $this->path = $path;
        $this->body = $body;
    }

    public static function capture(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        $route = $_GET['route'] ?? '';

        if (!is_string($route)) {
            throw new UnexpectedValueException('Invalid route.');
        }

        $path = '/' . trim($route, '/');

        $rawBody = file_get_contents('php://input');

        $body = [];

        if ($rawBody !== false && trim($rawBody) !== '') {
            $decoded = json_decode(
                $rawBody,
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            if (!is_array($decoded)) {
                throw new UnexpectedValueException(
                    'Request body must be a JSON object or array.'
                );
            }

            $body = $decoded;
        }

        return new self($method, $path, $body);
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getBody(): array
    {
        return $this->body;
    }
}