Proxy
==============================================================================================
[![Latest Stable Version](https://poser.pugx.org/web-fu/proxy/v)](https://packagist.org/packages/web-fu/proxy)
[![PHP Version Require](https://poser.pugx.org/web-fu/proxy/require/php)](https://packagist.org/packages/web-fu/proxy)
![Test status](https://github.com/web-fu/proxy/actions/workflows/tests.yaml/badge.svg)
![Static analysis status](https://github.com/web-fu/proxy/actions/workflows/static-analysis.yml/badge.svg)
![Code style status](https://github.com/web-fu/proxy/actions/workflows/code-style.yaml/badge.svg)

### A library that allows to create proxies for array and objects

This library allows to create proxies for arrays and objects.

This is a spin-off of the [PHP Dot Notation](https://github.com/web-fu/php-dot-notation) library.

## Installation
```bash
composer require web-fu/proxy
```

## Usage
For arrays:
```php
$element = [
    'foo' => 'bar',
];

$proxy = new ArrayProxy($element);

echo $proxy->has('foo'); //true
echo $proxy->isInitialised('foo'); //true
echo $proxy->get('foo'); //bar

$proxy->set('foo', 'baz');

echo $element['foo']; //baz
```

For objects:
```php
$element = new class() {
    public string $property = 'test';
    
    public function method(): string
    {
        return 'foo';
    }
};

$proxy = new ClassProxy($element);

echo $proxy->has('property'); //true
echo $proxy->isInitialised('property'); //true
echo $proxy->get('property'); //test

echo $proxy->has('method()'); //true
echo $proxy->isInitialised('method()'); //true
echo $proxy->get('method()'); //foo

$proxy->set('property', 'baz');

echo $element->property; //baz
```

For both:
```php
$element = [
    'foo' => 'bar',
];

$proxy = ProxyFactory::create($element);
```