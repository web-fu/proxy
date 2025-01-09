<?php

declare(strict_types=1);

/**
 * This file is part of web-fu/proxy
 *
 * @copyright Web-Fu <info@web-fu.it>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFu\Proxy;

use WebFu\Reflection\ReflectionClass;
use WebFu\Reflection\ReflectionMethod;
use WebFu\Reflection\ReflectionProperty;

class Proxy
{
    /**
     * @param array<mixed>|object $element
     */
    public function __construct(private array|object &$element)
    {
    }

    public function has(int|string $key): bool
    {
        if (is_object($this->element)) {
            $reflection = new ReflectionClass($this->element);

            $key = (string) $key;

            if ($reflection->hasProperty($key)) {
                return $reflection->getProperty($key)?->isPublic() ?? false;
            }

            if ($reflection->hasMethod('__get')) {
                return $reflection->getMethod('__get')->isPublic();
            }

            if (str_ends_with($key, '()')) {
                $method = str_replace('()', '', $key);

                if ($reflection->hasMethod($method)) {
                    return $reflection->getMethod($method)->isPublic();
                }
            }
        }

        return in_array($key, $this->getKeys(), true);
    }

    /**
     * @return array<int|string>
     */
    public function getKeys(): array
    {
        if (is_array($this->element)) {
            return array_keys($this->element);
        }

        $keys = [];

        $reflection = new ReflectionClass($this->element);

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $keys[] = $property->getName();
        }

        foreach (get_object_vars($this->element) as $property => $value) {
            $keys[] = $property;
        }

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $keys[] = $method->getName().'()';
        }

        return array_values(array_unique($keys));
    }

    public function get(int|string $key): mixed
    {
        if (!$this->has($key)) {
            throw new PathNotFoundException('Key `'.$key.'` not found');
        }

        if (is_array($this->element)) {
            return $this->element[$key];
        }

        $key = (string) $key;

        if (str_ends_with($key, '()')) {
            $method = str_replace('()', '', $key);

            return $this->element->{$method}();
        }

        return $this->element->{$key};
    }

    public function set(int|string $key, mixed $value): self
    {
        if (!$this->has($key)) {
            throw new PathNotFoundException('Key `'.$key.'` not found');
        }

        $key = (string) $key;

        if (str_ends_with($key, '()')) {
            throw new UnsupportedOperationException('Cannot set a class method');
        }

        if (is_array($this->element)) {
            $this->element[$key] = $value;

            return $this;
        }

        $this->element->{$key} = $value;

        return $this;
    }

    public function isInitialised(int|string $key): bool
    {
        if (!$this->has($key)) {
            throw new PathNotFoundException('Key `'.$key.'` not found');
        }

        if (is_array($this->element)) {
            return isset($this->element[$key]);
        }

        $key = (string) $key;

        $reflection = new ReflectionClass($this->element);

        if (str_ends_with($key, '()')) {
            $method = str_replace('()', '', $key);

            return $reflection->hasMethod($method);
        }

        if ($this->dynamicKeysAllowed()) {
            return isset($this->element->{$key});
        }

        return $reflection->getProperty($key)?->isInitialized($this->element) ?? false;
    }

    public function getProxy(int|string $key): self
    {
        if (!$this->has($key)) {
            throw new PathNotFoundException('Key `'.$key.'` not found');
        }

        $value = $this->get($key);

        if (!is_array($value) && !is_object($value)) {
            throw new UnsupportedOperationException('Cannot create a proxy for a scalar value');
        }

        return new self($value);
    }

    public function create(int|string $key, mixed $value): self
    {
        if (
            $this->has($key)
            && $this->isInitialised($key)
        ) {
            return $this;
        }

        if (is_array($this->element)) {
            $this->element[$key] = $value;

            return $this;
        }

        if (!$this->dynamicKeysAllowed()) {
            throw new UnsupportedOperationException('Cannot create a new property');
        }

        $key = (string) $key;

        $this->element->{$key} = $value;

        return $this;
    }

    public function unset(int|string $key): self
    {
        if (!$this->has($key)) {
            return $this;
        }

        if (is_array($this->element)) {
            unset($this->element[$key]);

            return $this;
        }

        $key = (string) $key;

        if (str_ends_with($key, '()')) {
            throw new UnsupportedOperationException('Cannot unset a class method');
        }

        unset($this->element->{$key});

        return $this;
    }

    public function dynamicKeysAllowed(): bool
    {
        if (is_array($this->element)) {
            return true;
        }

        $reflection = new ReflectionClass($this->element);

        $checkStdClass  = 'stdClass' === $reflection->getName();
        $checkAttribute = [] !== $reflection->getAttributes('AllowDynamicProperties');
        $checkMethod    = $reflection->hasMethod('__set');

        return $checkStdClass || $checkAttribute || $checkMethod;
    }
}
