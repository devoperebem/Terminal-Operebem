<?php

namespace App\Core;

class Router
{
    private array $routes = [];
    private array $middleware = [];
    private array $globalMiddleware = [];

    public function get(string $path, $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post(string $path, $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    public function put(string $path, $handler, array $middleware = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middleware);
    }

    public function delete(string $path, $handler, array $middleware = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    public function patch(string $path, $handler, array $middleware = []): void
    {
        $this->addRoute('PATCH', $path, $handler, $middleware);
    }

    /**
     * Adiciona um middleware global que será executado em todas as rotas
     * @param string $middlewareClass
     */
    public function addGlobalMiddleware(string $middlewareClass): void
    {
        $this->globalMiddleware[] = $middlewareClass;
    }

    private function addRoute(string $method, string $path, $handler, array $middleware): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $this->normalizePath($path),
            'handler' => $handler,
            'middleware' => $middleware
        ];
    }

    public function resolve(): void
    {
        // Executar middlewares globais primeiro
        foreach ($this->globalMiddleware as $middlewareClass) {
            $middleware = new $middlewareClass();
            if (!$middleware->handle()) {
                return;
            }
        }

        $method = $_SERVER['REQUEST_METHOD'];
        $path = $this->getCurrentPath();

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $this->matchPath($route['path'], $path)) {
                $params = $this->extractParams($route['path'], $path);
                
                // Executar middleware
                foreach ($route['middleware'] as $middlewareClass) {
                    $middleware = new $middlewareClass();
                    if (!$middleware->handle()) {
                        return;
                    }
                }
                
                // Executar handler
                if (is_array($route['handler'])) {
                    [$controller, $ctrlMethod] = $route['handler'];
                    $controllerInstance = new $controller();

                    // Mapear parâmetros por nome usando Reflection
                    $refMethod = new \ReflectionMethod($controller, $ctrlMethod);
                    $args = [];
                    foreach ($refMethod->getParameters() as $p) {
                        $name = $p->getName();
                        $value = $params[$name] ?? null;
                        // Casting básico por tipo
                        $type = $p->getType();
                        if ($type && $value !== null) {
                            $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : null;
                            if ($typeName === 'int') { $value = (int) $value; }
                            elseif ($typeName === 'float') { $value = (float) $value; }
                            elseif ($typeName === 'bool') { $value = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE); }
                            elseif ($typeName === 'string') { $value = (string) $value; }
                        }
                        if ($value === null && $p->isDefaultValueAvailable()) {
                            $value = $p->getDefaultValue();
                        }
                        $args[] = $value;
                    }

                    // Fallback: se método espera 1 parâmetro e não houve match por nome,
                    // passar o array completo de parâmetros da rota (compatibilidade)
                    if ($refMethod->getNumberOfParameters() === 1) {
                        $onlyParam = $refMethod->getParameters()[0];
                        $expectsArray = $onlyParam->hasType() && $onlyParam->getType() instanceof \ReflectionNamedType && $onlyParam->getType()->getName() === 'array';
                        if (($args[0] === null && !empty($params)) || $expectsArray) {
                            $args = [$params];
                        }
                    }

                    $refMethod->invokeArgs($controllerInstance, $args);
                } else {
                    // Handler simples (Closure/Callable)
                    if (is_callable($route['handler'])) {
                        $refFunc = new \ReflectionFunction($route['handler']);
                        $args = [];
                        foreach ($refFunc->getParameters() as $p) {
                            $name = $p->getName();
                            $value = $params[$name] ?? null;
                            $type = $p->getType();
                            if ($type && $value !== null) {
                                $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : null;
                                if ($typeName === 'int') { $value = (int) $value; }
                                elseif ($typeName === 'float') { $value = (float) $value; }
                                elseif ($typeName === 'bool') { $value = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE); }
                                elseif ($typeName === 'string') { $value = (string) $value; }
                            }
                            if ($value === null && $p->isDefaultValueAvailable()) {
                                $value = $p->getDefaultValue();
                            }
                            $args[] = $value;
                        }
                        // Fallback compatibilidade para closures que esperam $params
                        if ($refFunc->getNumberOfParameters() === 1) {
                            $onlyParam = $refFunc->getParameters()[0];
                            $expectsArray = $onlyParam->hasType() && $onlyParam->getType() instanceof \ReflectionNamedType && $onlyParam->getType()->getName() === 'array';
                            if (($args[0] === null && !empty($params)) || $expectsArray) {
                                $args = [$params];
                            }
                        }
                        $refFunc->invokeArgs($args);
                    }
                }
                
                return;
            }
        }

        // 404 - Rota não encontrada
        http_response_code(404);
        include dirname(__DIR__, 2) . '/src/Views/errors/404.php';
    }

    private function normalizePath(string $path): string
    {
        return '/' . trim($path, '/');
    }

    private function getCurrentPath(): string
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        if (!is_string($requestUri) || $requestUri === '') {
            $requestUri = '/';
        }
        $path = parse_url($requestUri, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            $path = '/';
        }
        return $this->normalizePath($path);
    }

    private function matchPath(string $routePath, string $currentPath): bool
    {
        // Converter parâmetros {id} para regex
        $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $routePath);
        $pattern = '#^' . $pattern . '$#';
        
        return preg_match($pattern, $currentPath);
    }

    private function extractParams(string $routePath, string $currentPath): array
    {
        $params = [];
        
        // Extrair nomes dos parâmetros
        preg_match_all('/\{([^}]+)\}/', $routePath, $paramNames);
        
        // Extrair valores dos parâmetros
        $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $routePath);
        $pattern = '#^' . $pattern . '$#';
        
        if (preg_match($pattern, $currentPath, $matches)) {
            array_shift($matches); // Remove o match completo
            
            foreach ($paramNames[1] as $index => $name) {
                $params[$name] = $matches[$index] ?? null;
            }
        }
        
        return $params;
    }
}
