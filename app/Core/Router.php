<?php

namespace App\Core;

class Router
{
    private $routes = [];

    public function get($path, $handler)
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post($path, $handler)
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function put($path, $handler)
    {
        $this->addRoute('PUT', $path, $handler);
    }

    public function patch($path, $handler)
    {
        $this->addRoute('PATCH', $path, $handler);
    }

    public function delete($path, $handler)
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    private function addRoute($method, $path, $handler)
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }

    public function dispatch()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $this->matchPath($route['path'], $path, $params)) {
                return $this->handle($route['handler'], $params);
            }
        }

        $this->sendNotFound();
    }

    private function matchPath($routePath, $requestPath, &$params)
    {
        $routeRegex = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[a-zA-Z0-9_-]+)', $routePath);
        $routeRegex = '#^' . $routeRegex . '$#';

        if (preg_match($routeRegex, $requestPath, $matches)) {
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            return true;
        }

        return false;
    }

    private function handle($handler, $params)
    {
        if (is_callable($handler)) {
            return call_user_func($handler, $params);
        }

        if (is_array($handler) && count($handler) === 2) {
            $controllerClass = $handler[0];
            $method = $handler[1];
            $controller = new $controllerClass();
            return call_user_func([$controller, $method], $params);
        }
        
        throw new \Exception("Invalid route handler");
    }

    private function sendNotFound()
    {
        header("HTTP/1.0 404 Not Found");
        echo json_encode(['error' => 'Not Found']);
    }
}
