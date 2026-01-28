<?php

declare(strict_types=1);

namespace CoreFly\Core;

class Router
{
    private array $routes = [];
    private string $prefix = '';
    private array $middleware = [];

    public function get(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    public function put(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middleware);
    }

    public function delete(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    public function group(string $prefix, callable $callback, array $middleware = []): void
    {
        $previousPrefix = $this->prefix;
        $previousMiddleware = $this->middleware;

        $this->prefix .= $prefix;
        $this->middleware = array_merge($this->middleware, $middleware);

        $callback($this);

        $this->prefix = $previousPrefix;
        $this->middleware = $previousMiddleware;
    }

    private function addRoute(string $method, string $path, callable|array $handler, array $middleware = []): void
    {
        $path = $this->prefix . $path;
        // Remove trailing slash if not root
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }
        
        // Convert path parameters {id} to regex groups
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = "#^" . $pattern . "$#";

        $this->routes[$method][$pattern] = [
            'handler' => $handler,
            'middleware' => array_merge($this->middleware, $middleware)
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        // Strip query string
        $uri = parse_url($uri, PHP_URL_PATH);
        // Remove trailing slash if not root
        if ($uri !== '/') {
            $uri = rtrim($uri, '/');
        }
        // Ensure leading slash
        if ($uri === '') $uri = '/';

        if (!isset($this->routes[$method])) {
            $this->sendNotFound();
            return;
        }

        foreach ($this->routes[$method] as $pattern => $route) {
            if (preg_match($pattern, $uri, $matches)) {
                // Filter named parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                // Execute Middleware
                foreach ($route['middleware'] as $middleware) {
                    if (is_string($middleware) && class_exists($middleware)) {
                        // Static handle method
                        if (method_exists($middleware, 'handle')) {
                            $middleware::handle($uri);
                        } else {
                            // Instantiable middleware
                            $mw = new $middleware();
                            $mw($uri);
                        }
                    } elseif (is_callable($middleware)) {
                        $middleware($uri);
                    }
                }

                // Execute Handler
                $handler = $route['handler'];
                if (is_array($handler)) {
                    [$controllerClass, $methodName] = $handler;
                    $controller = new $controllerClass();
                    echo $controller->$methodName(...array_values($params));
                } elseif (is_callable($handler)) {
                    echo $handler(...array_values($params));
                }
                return;
            }
        }

        $this->sendNotFound();
    }

    private function sendNotFound(): void
    {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Not Found']);
    }
}
