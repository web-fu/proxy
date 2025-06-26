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

use WebFu\Proxy\Exception\KeyNotFoundException;
use WebFu\Proxy\Exception\UnsupportedOperationException;
use WebFu\Reflection\ReflectionClass;
use WebFu\Reflection\ReflectionMethod;
use WebFu\Reflection\ReflectionObject;
use WebFu\Reflection\ReflectionProperty;

class Proxy
{
    /**
     * @param array<mixed>|object $element
     */
    public function __construct(private array|object &$element)
    {
    }

    /**
     * Check if a key exists in the element.
     *
     * @param int|string $key
     *
     * @return bool
     */
    public function has(int|string $key): bool
    {
        if (is_array($this->element)) {
            return array_key_exists($key, $this->element);
        }

        $keys = [];

        $reflection = new ReflectionClass($this->element);

        if ($reflection->hasMethod('__get')) {
            return $reflection->getMethod('__get')->isPublic();
        }

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $keys[] = $property->getName();
        }

        foreach (get_object_vars($this->element) as $property => $value) {
            $keys[] = $property;
        }

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $keys[] = $method->getName().'()';
        }

        return in_array($key, $keys, true);
    }

    /**
     * Return the list of keys in the element.
     *
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

    /**
     * Return the value of a key in the element, failing if the key does not exist.
     *
     * @param int|string $key
     *
     * @throws KeyNotFoundException
     *
     * @return mixed
     */
    public function get(int|string $key): mixed
    {
        if (!$this->has($key)) {
            throw new KeyNotFoundException($key);
        }

        if (is_array($this->element)) {
            return $this->element[$key];
        }

        $key = (string) $key;

        $reflection = new ReflectionObject($this->element);

        if (str_ends_with($key, '()')) {
            $method = str_replace('()', '', $key);

            return $reflection->getMethod($method)->invoke($this->element);
        }

        if ($reflection->hasProperty($key)) {
            return $reflection->getProperty($key)?->getValue($this->element);
        }

        return $this->element->{$key};
    }

    /**
     * Set the value of a key in the element, failing if the key does not exist.
     *
     * @param int|string $key
     * @param mixed      $value
     *
     * @throws UnsupportedOperationException
     * @throws KeyNotFoundException
     *
     * @return $this
     */
    public function set(int|string $key, mixed $value): self
    {
        if (!$this->has($key)) {
            throw new KeyNotFoundException($key);
        }

        $key = (string) $key;

        if (str_ends_with($key, '()')) {
            throw new UnsupportedOperationException('Cannot set a class method');
        }

        if (is_array($this->element)) {
            $this->element[$key] = $value;

            return $this;
        }

        $reflectionObject = new ReflectionObject($this->element);

        $reflectionObject->getProperty($key)?->setValue($this->element, $value);

        return $this;
    }

    /**
     * Check if a key is initialised in the element, failing if the key does not exist.
     *
     * @param int|string $key
     *
     * @throws KeyNotFoundException
     *
     * @return bool
     */
    public function isInitialised(int|string $key): bool
    {
        if (!$this->has($key)) {
            throw new KeyNotFoundException($key);
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

        if ($reflection->hasProperty($key)) {
            return $reflection->getProperty($key)?->isInitialized($this->element) ?? false;
        }

        if ($this->dynamicKeysAllowed()) {
            return isset($this->element->{$key});
        }

        return false;
    }

    /**
     * Get a proxy for a key in the element, failing if the key does not exist or the value is not an array or an object.
     *
     * @param int|string $key
     *
     * @throws UnsupportedOperationException
     * @throws KeyNotFoundException
     *
     * @return self
     */
    public function getProxy(int|string $key): self
    {
        if (!$this->has($key)) {
            throw new KeyNotFoundException($key);
        }

        $value = $this->get($key);

        if (!is_array($value) && !is_object($value)) {
            throw new UnsupportedOperationException('Cannot create a proxy for a scalar value');
        }

        return new self($value);
    }

    /**
     * Create a new key in the element, failing if it's not possible.
     *
     * @param int|string $key
     * @param mixed      $value
     *
     * @throws UnsupportedOperationException
     *
     * @return $this
     */
    public function create(int|string $key, mixed $value): self
    {
        if (is_array($this->element)) {
            $this->element[$key] = $value;

            return $this;
        }

        if (
            !$this->has($key)
            && !$this->dynamicKeysAllowed()
        ) {
            throw new UnsupportedOperationException('Cannot create a new property');
        }

        $key = (string) $key;

        $this->element->{$key} = $value;

        return $this;
    }

    /**
     * Unset a key in the element.
     *
     * @param int|string $key
     *
     * @throws UnsupportedOperationException
     *
     * @return $this
     */
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

    /**
     * Check if dynamic keys are allowed in the element.
     *
     * @return bool
     */
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
