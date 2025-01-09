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

## Create a Proxy
```php
$element = [
    'foo' => 'bar',
    'zod' => [
        'baz' => 'qux',
    ],
];

$proxy = new Proxy($element);
```

## Getting and setting values
```php
echo $proxy->get('foo'); //bar
$proxy->set('foo', 'baz');
echo $element['foo']; //baz
```

## Checking keys
```php
echo $proxy->has('foo'); //true
echo $proxy->isInitialised('foo'); //true
echo $proxy->dynamicKeysAllowed(); //true;
```

## Creating and destroying keys
```php
$proxy->create('rol', 'foo');
echo $element['rol']; //foo

$proxy->unset('zod');
var_dump($element); //['foo' => 'bar']
```

## Getting a proxy for a key
```php
$proxy->getProxy('zod')->set('baz', 'qux');
echo $element['zod']['baz']; //qux
``` 

See `/examples` folder for full examples