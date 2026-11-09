<?php

declare(strict_types=1);

namespace App\Http;

use App\Exceptions\HttpException;
use App\Exceptions\NotFoundException;
use Closure;
use ReflectionMethod;
use ReflectionNamedType;

final class Router
{
    /** @var array<int, array{method:string, uri:string, pattern:string, action:mixed, middleware:array<int, class-string>, csrf:bool}> */
    private array $routes = [];

    /** @var array<int, array{prefix:string, middleware:array<int, class-string>}> */
    private array $groupStack = [];

    public function get(string $uri, array|Closure $action, array $options = []): self
    {
        return $this->add('GET', $uri, $action, $options);
    }

    public function post(string $uri, array|Closure $action, array $options = []): self
    {
        return $this->add('POST', $uri, $action, $options);
    }

    public function put(string $uri, array|Closure $action, array $options = []): self
    {
        return $this->add('PUT', $uri, $action, $options);
    }

    public function delete(string $uri, array|Closure $action, array $options = []): self
    {
        return $this->add('DELETE', $uri, $action, $options);
    }

    /**
     * @param array{prefix?:string, middleware?:array<int, class-string>} $attributes
     */
    public function group(array $attributes, callable $callback): void
    {
        $this->groupStack[] = [
            'prefix' => trim((string) ($attributes['prefix'] ?? ''), '/'),
            'middleware' => (array) ($attributes['middleware'] ?? []),
        ];

        $callback($this);

        array_pop($this->groupStack);
    }

    /**
     * @param array<int, class-string> $middleware
     */
    private function add(string $method, string $uri, array|Closure $action, array $options = []): self
    {
        $prefix = '';
        $groupMiddleware = [];

        foreach ($this->groupStack as $group) {
            $prefix .= '/' . $group['prefix'];
            $groupMiddleware = array_merge($groupMiddleware, $group['middleware']);
        }

        $fullUri = '/' . trim($prefix . '/' . trim($uri, '/'), '/');
        $fullUri = $fullUri === '' ? '/' : $fullUri;

        $this->routes[] = [
            'method' => $method,
            'uri' => $fullUri,
            'pattern' => $this->compile($fullUri),
            'action' => $action,
            'middleware' => array_values(array_merge($groupMiddleware, (array) ($options['middleware'] ?? []))),
            'csrf' => (bool) ($options['csrf'] ?? true),
        ];

        return $this;
    }

    public function dispatch(Request $request): void
    {
        $match = $this->match($request->method(), $request->path());

        if ($match === null) {
            throw new NotFoundException('صفحه مورد نظر یافت نشد.');
        }

        foreach ($match['route']['middleware'] as $middleware) {
            /** @var object $instance */
            $instance = new $middleware();
            $instance->handle($request);
        }

        if ($request->method() === 'POST' && $match['route']['csrf']) {
            if (!\App\Support\Csrf::verify($request->csrfToken())) {
                throw new HttpException(419, 'اعتبار فرم منقضی شده است. صفحه را دوباره بارگذاری کنید.');
            }
        }

        $result = $this->invoke($match['route']['action'], $match['params'], $request);

        if (is_array($result)) {
            Response::json($result);
        } elseif (is_string($result)) {
            echo $result;
        }
    }

    /**
     * @return array{route:array<string,mixed>, params:array<string,string>}|null
     */
    private function match(string $method, string $path): ?array
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $path, $matches) !== 1) {
                continue;
            }

            $params = [];

            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }

            return ['route' => $route, 'params' => $params];
        }

        return null;
    }

    private function compile(string $uri): string
    {
        $parts = preg_split('/(\{[a-zA-Z_][a-zA-Z0-9_]*\})/', $uri, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
        $pattern = '';

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            if (preg_match('/^\{([a-zA-Z_][a-zA-Z0-9_]*)\}$/', $part, $m) === 1) {
                $pattern .= '(?P<' . $m[1] . '>[^/]+)';
            } else {
                $pattern .= preg_quote($part, '#');
            }
        }

        return '#^' . $pattern . '$#u';
    }

    /**
     * @param array{0:class-string, 1:string}|Closure $action
     * @param array<string, string> $params
     */
    private function invoke(array|Closure $action, array $params, Request $request): mixed
    {
        if ($action instanceof Closure) {
            return $action($request, ...$params);
        }

        [$class, $method] = $action;
        $controller = new $class();

        $reflection = new ReflectionMethod($controller, $method);
        $args = [];

        foreach ($reflection->getParameters() as $parameter) {
            $name = $parameter->getName();
            $type = $parameter->getType();

            if ($name === 'request' && $type instanceof ReflectionNamedType && $type->getName() === Request::class) {
                $args[] = $request;
                continue;
            }

            if (array_key_exists($name, $params)) {
                $args[] = $this->cast($params[$name], $type);
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $args[] = $parameter->getDefaultValue();
                continue;
            }

            $args[] = null;
        }

        return $reflection->invokeArgs($controller, $args);
    }

    private function cast(string $value, ?\ReflectionType $type): mixed
    {
        if (!$type instanceof ReflectionNamedType) {
            return $value;
        }

        return match ($type->getName()) {
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => in_array(strtolower($value), ['1', 'true', 'on', 'yes'], true),
            default => $value,
        };
    }
}
