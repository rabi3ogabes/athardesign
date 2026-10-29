<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<int,array{method:string,regex:string,keys:array,handler:callable|array,middleware:array}> */
    private array $routes = [];
    private array $groupPrefix = [];
    private array $groupMiddleware = [];

    public function group(string $prefix, array $middleware, callable $fn): void
    {
        $this->groupPrefix[] = rtrim($prefix, '/');
        $this->groupMiddleware[] = $middleware;
        $fn($this);
        array_pop($this->groupPrefix);
        array_pop($this->groupMiddleware);
    }

    private function add(string $method, string $pattern, callable|array $handler): void
    {
        $prefix = implode('', $this->groupPrefix);
        $full = $prefix . '/' . ltrim($pattern, '/');
        $full = $full === '' ? '/' : rtrim($full, '/');
        if ($full === '') $full = '/';
        $keys = [];
        $regex = preg_replace_callback('/\{([a-zA-Z_]+)(\:[^}]+)?\}/', static function ($m) use (&$keys) {
            $keys[] = $m[1];
            $sub = isset($m[2]) ? substr($m[2], 1) : '[^/]+';
            return '(' . $sub . ')';
        }, $full);
        $this->routes[] = [
            'method' => $method,
            'regex' => '#^' . $regex . '$#u',
            'keys' => $keys,
            'handler' => $handler,
            'middleware' => array_merge(...($this->groupMiddleware ?: [[]])),
        ];
    }

    public function get(string $p, callable|array $h): void { $this->add('GET', $p, $h); }
    public function post(string $p, callable|array $h): void { $this->add('POST', $p, $h); }
    public function any(string $p, callable|array $h): void { $this->add('ANY', $p, $h); }

    /** Dispatch; returns false when no route matched. */
    public function dispatch(Request $request): bool
    {
        $path = rtrim($request->path, '/') ?: '/';
        $method = $request->method === 'HEAD' ? 'GET' : $request->method;
        foreach ($this->routes as $route) {
            if ($route['method'] !== 'ANY' && $route['method'] !== $method) continue;
            if (!preg_match($route['regex'], $path, $m)) continue;
            array_shift($m);
            $params = [];
            foreach ($route['keys'] as $i => $k) {
                $params[$k] = $m[$i] ?? null;
            }
            $request->params = $params;
            foreach ($route['middleware'] as $mw) {
                $mw($request);
            }
            $handler = $route['handler'];
            if (is_array($handler) && is_string($handler[0])) {
                $handler = [new $handler[0](), $handler[1]];
            }
            $result = $handler($request, ...array_values($params));
            if (is_string($result)) {
                Response::html($result);
            }
            return true;
        }
        return false;
    }
}
