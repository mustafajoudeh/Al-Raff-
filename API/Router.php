<?php
declare(strict_types=1);

class Router
{
    private array $routes = [];

    public function get(string $path, callable $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, callable $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function put(string $path, callable $handler, array $middleware = []): void
    {
        $this->add('PUT', $path, $handler, $middleware);
    }

    public function patch(string $path, callable $handler, array $middleware = []): void
    {
        $this->add('PATCH', $path, $handler, $middleware);
    }

    public function delete(string $path, callable $handler, array $middleware = []): void
    {
        $this->add('DELETE', $path, $handler, $middleware);
    }

    private function add(string $method, string $path, callable $handler, array $middleware = []): void
    {
        $normalized = $this->normalizePath($path);
        $pattern = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $normalized) . '$#';

        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(Request $request): void
    {
        $pathExists = false;

        foreach ($this->routes as $route) {

            if (!preg_match($route['pattern'], $request->getPath(), $matches)) {
                continue;
            }

            $pathExists = true;

            if ($route['method'] !== $request->getMethod()) {
                continue;
            }

            $request->setParams(array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY));

            foreach ($route['middleware'] as $middleware) {
                $middleware($request);
            }

            ($route['handler'])($request);

            return;
        }

        if ($pathExists) {
            Response::json(405, false, 'Method Not Allowed');
        }

        Response::json(404, false, 'Route Not Found');
    }

    private function normalizePath(string $path): string
    {
        return '/' . trim($path, '/');
    }
}