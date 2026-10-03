<?php
declare(strict_types=1);

namespace App\Core;

use Closure;
use RuntimeException;

final class Router
{
    /** @var array<string, array<string, array{handler: mixed, middleware: array}>> */
    private array $routes = [
        'GET'    => [],
        'POST'   => [],
        'PUT'    => [],
        'PATCH'  => [],
        'DELETE' => [],
    ];

    public function get(string $path, mixed $handler, array $middleware = []): void    { $this->add('GET', $path, $handler, $middleware); }
    public function post(string $path, mixed $handler, array $middleware = []): void   { $this->add('POST', $path, $handler, $middleware); }
    public function put(string $path, mixed $handler, array $middleware = []): void    { $this->add('PUT', $path, $handler, $middleware); }
    public function patch(string $path, mixed $handler, array $middleware = []): void  { $this->add('PATCH', $path, $handler, $middleware); }
    public function delete(string $path, mixed $handler, array $middleware = []): void { $this->add('DELETE', $path, $handler, $middleware); }

    private function add(string $method, string $path, mixed $handler, array $middleware): void
    {
        // Normalise path: always starts with /, no trailing slash (except "/")
        $path = '/' . trim($path, '/');
        if ($path === '/') $path = '/';

        $this->routes[$method][$path] = [
            'handler'    => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $path   = $request->path();

        $methodMatchesButPathMissed = false;

        foreach ($this->routes as $routeMethod => $routeList) {
            foreach ($routeList as $route => $def) {
                $pattern = $this->buildPattern($route);

                if (!preg_match($pattern, $path, $matches)) {
                    continue;
                }

                if ($routeMethod !== $method) {
                    $methodMatchesButPathMissed = true;
                    continue;
                }

                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                return $this->runPipeline($request, $def, $params);
            }
        }

        if ($methodMatchesButPathMissed) {
            return Response::error(405, 'Method not allowed for this URL.');
        }

        return Response::notFound('The page you requested does not exist.');
    }

    /**
     * Turn "/products/{id}/edit" into a regex with named captures.
     */
    private function buildPattern(string $route): string
    {
        $pattern = preg_replace('#\{([a-zA-Z_]\w*)\}#', '(?P<$1>[^/]+)', $route);
        return '#^' . $pattern . '$#';
    }

    /**
     * Run the middleware chain then the controller.
     */
    private function runPipeline(Request $request, array $def, array $params): Response
    {
        $core = function (Request $req) use ($def, $params): Response {
            return $this->invoke($def['handler'], $req, $params);
        };

        $pipeline = array_reduce(
            array_reverse($def['middleware']),
            function (callable $next, string $mwSpec): callable {
                return function (Request $r) use ($mwSpec, $next): Response {
                    [$class, $args] = array_pad(explode(':', $mwSpec, 2), 2, null);

                    if (!class_exists($class)) {
                        throw new RuntimeException("Middleware not found: {$class}");
                    }

                    $instance = new $class();
                    $middlewareParams = ($args === null || $args === '') ? [] : explode(',', $args);

                    return $instance->handle($r, $next, ...$middlewareParams);
                };
            },
            $core
        );

        return $pipeline($request);
    }

    /**
     * Invoke the route handler.
     *
     * Supports three handler forms:
     *   1. Closure:           function (Request $r) { ... }
     *   2. Array callable:    [HomeController::class, 'index']
     *   3. String class@meth: 'HomeController@index'
     */
    private function invoke(mixed $handler, Request $req, array $params): Response
    {
        if ($handler instanceof Closure) {
            $result = $handler($req, ...array_values($params));
        } elseif (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;

            if (!class_exists($class)) {
                throw new RuntimeException("Controller not found: {$class}");
            }
            if (!method_exists($class, $method)) {
                throw new RuntimeException("Method {$class}::{$method} does not exist.");
            }

            $result = (new $class())->{$method}($req, ...array_values($params));
        } elseif (is_string($handler) && str_contains($handler, '@')) {
            [$class, $method] = explode('@', $handler, 2);

            if (!class_exists($class)) {
                throw new RuntimeException("Controller not found: {$class}");
            }
            if (!method_exists($class, $method)) {
                throw new RuntimeException("Method {$class}::{$method} does not exist.");
            }

            $result = (new $class())->{$method}($req, ...array_values($params));
        } else {
            throw new RuntimeException('Unsupported route handler format.');
        }

        // Normalise the return value to a Response
        if ($result instanceof Response) return $result;
        if (is_string($result))          return Response::html($result);
        if (is_array($result))           return Response::json($result);
        if ($result === null)            return Response::html('');

        throw new RuntimeException(
            'Route handler must return Response, string, array, or null. Got: ' . gettype($result)
        );
    }
}