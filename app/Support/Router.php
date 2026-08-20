<?php
declare(strict_types=1);

namespace App\Support;

final class Router
{
    /** @var list<array{methods: list<string>, pattern: string, handler: callable|array{0: class-string, 1: string}, name?: string}> */
    private array $routes = [];

    public function get(string $pattern, callable|array $handler, ?string $name = null): self
    {
        return $this->add(['GET'], $pattern, $handler, $name);
    }

    public function post(string $pattern, callable|array $handler, ?string $name = null): self
    {
        return $this->add(['POST'], $pattern, $handler, $name);
    }

    public function put(string $pattern, callable|array $handler, ?string $name = null): self
    {
        return $this->add(['PUT'], $pattern, $handler, $name);
    }

    public function delete(string $pattern, callable|array $handler, ?string $name = null): self
    {
        return $this->add(['DELETE'], $pattern, $handler, $name);
    }

    public function any(string $pattern, callable|array $handler, ?string $name = null): self
    {
        return $this->add(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], $pattern, $handler, $name);
    }

    /** @param list<string> $methods */
    public function add(array $methods, string $pattern, callable|array $handler, ?string $name = null): self
    {
        $route = [
            'methods' => array_map('strtoupper', $methods),
            'pattern' => $pattern,
            'handler' => $handler,
        ];
        if ($name !== null) {
            $route['name'] = $name;
        }
        $this->routes[] = $route;
        return $this;
    }

    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        // Browsers and probes often send HEAD; treat like GET for route matching.
        if ($method === 'HEAD') {
            $method = 'GET';
        }
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $base = rtrim(Env::get('APP_BASE_PATH', '') ?: '', '/');
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base)) ?: '/';
        }
        $path = '/' . trim($path, '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }
        // Support shared-hosting style URLs: /login.php, /admin/login.php
        if (str_ends_with(strtolower($path), '.php')) {
            $path = substr($path, 0, -4);
            if ($path === '' || $path === false) {
                $path = '/';
            }
        }

        foreach ($this->routes as $route) {
            if (!in_array($method, $route['methods'], true) && !in_array('ANY', $route['methods'], true)) {
                continue;
            }
            $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $route['pattern']);
            $regex = '#^' . $regex . '$#';
            if (!preg_match($regex, $path, $matches)) {
                continue;
            }
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            $handler = $route['handler'];
            if (is_array($handler)) {
                [$class, $action] = $handler;
                $controller = new $class();
                $controller->{$action}(...array_values($params));
                return;
            }
            $handler(...array_values($params));
            return;
        }

        http_response_code(404);
        View::make('public/404', ['title' => 'Page not found'], 'layouts/public');
    }
}
