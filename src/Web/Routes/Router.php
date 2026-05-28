<?php

declare(strict_types=1);

namespace PhpMvc\Routes;

final class Router
{
    /** @var array<string, Route[]> */
    private array $routesByMethod = [];

    /**
     * @param Route[] $routes
     */
    public function __construct(private array $routes = [])
    {
        foreach ($this->routes as $route) {
            $this->indexRoute($route);
        }
    }

    /**
     * @return Route[]
     */
    public function getRoutes(): array
    {
        return array_values($this->routes);
    }

    public function register(Route $route): void
    {
        if (!empty(array_filter($this->routes, fn (Route $currentRoute) => $currentRoute->equals($route)))) {
            throw new DuplicatedRouteException($route);
        }
        $this->routes[] = $route;
        $this->indexRoute($route);
    }

    public function get(RouteMethod $method, string $path): Route
    {
        $key = $method->value;
        $candidates = $this->routesByMethod[$key] ?? $this->routes;
        $matches = array_values(array_filter($candidates, fn (Route $route) => $route->match($method, $path)));
        if (empty($matches)) {
            throw new RouteDoesNotFoundException($method, $path);
        }

        return $matches[0];
    }

    public function getFromControllerAndAction(string $controller, string $action): ?Route
    {
        $matches = array_values(
            array_filter(
                $this->routes,
                fn (Route $route) => $route->controller === $controller && $route->action === $action
            )
        );

        if (empty($matches)) {
            return null;
        }

        return $matches[0];
    }

    private function indexRoute(Route $route): void
    {
        $key = $route->method->value;
        if (!array_key_exists($key, $this->routesByMethod)) {
            $this->routesByMethod[$key] = [];
        }
        $this->routesByMethod[$key][] = $route;
    }
}
