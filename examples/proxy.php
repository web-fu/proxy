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

use WebFu\Proxy\Proxy;

require __DIR__.'/../vendor/autoload.php';

// Array
$array = [
    'foo' => 'bar',
    'zod' => [
        'baz' => 'qux',
    ],
];

$proxy = new Proxy($array);

// Getting and setting
echo $proxy->get('foo'); // bar
echo PHP_EOL;

$proxy->set('foo', 'baz');

echo $array['foo']; // baz
echo PHP_EOL;

// Checking keys
echo $proxy->has('foo'); // true
echo PHP_EOL;
echo $proxy->isInitialised('foo'); // true
echo PHP_EOL;
echo $proxy->dynamicKeysAllowed(); // true
echo PHP_EOL;

// Creating and destroying keys
$proxy->create('bar', 'test');
echo $proxy->get('bar'); // test
echo PHP_EOL;
$proxy->unset('bar');
echo $proxy->has('bar'); // false
echo PHP_EOL;

// Getting a proxy from a key
$proxy->getProxy('zod')->set('baz', 'qux');
echo $array['zod']['baz']; // qux
echo PHP_EOL;

// Object
$object = new class {
    public string $property = 'test';

    public function method(): string
    {
        return 'foo';
    }
};

$proxy = new Proxy($object);

// Getting and setting
echo $proxy->get('property'); // test
$proxy->set('property', 'baz');
echo $object->property; // baz
echo PHP_EOL;
echo $proxy->get('method()'); // foo
echo PHP_EOL;

// Checking keys
echo $proxy->has('property'); // true
echo PHP_EOL;
echo $proxy->isInitialised('property'); // true
echo PHP_EOL;
echo $proxy->has('method()'); // true
echo PHP_EOL;
echo $proxy->isInitialised('method()'); // true
echo PHP_EOL;
echo $proxy->dynamicKeysAllowed(); // false
echo PHP_EOL;

// Creating and destroying keys
$object = new stdClass();
$proxy  = new Proxy($object);

$proxy->create('foo', 'bar');

echo $object->foo; // bar
echo PHP_EOL;

$proxy->unset('foo');
echo isset($object->foo); // false

// Getting a proxy from a key
$external = new class {
    public object $internal;

    public function __construct()
    {
        $this->internal = new class {
            public string $property = 'test';
        };
    }
};

$proxy = new Proxy($external);
$proxy->getProxy('internal')->set('property', 'baz');

echo $external->internal->property; // baz
echo PHP_EOL;
