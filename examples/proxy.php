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

use WebFu\Proxy\ArrayProxy;

require __DIR__.'/../vendor/autoload.php';

$array = [
    'foo' => 'bar',
];

$proxy = new ArrayProxy($array);

echo $proxy->has('foo'); // true
echo PHP_EOL;
echo $proxy->isInitialised('foo'); // true
echo PHP_EOL;
echo $proxy->get('foo'); // bar
echo PHP_EOL;

$proxy->set('foo', 'baz');

echo $array['foo']; // baz
echo PHP_EOL;

$object = new class {
    public string $property = 'test';

    public function method(): string
    {
        return 'foo';
    }
};

$proxy = new WebFu\Proxy\ClassProxy($object);

echo $proxy->has('property'); // true
echo PHP_EOL;
echo $proxy->isInitialised('property'); // true
echo PHP_EOL;
echo $proxy->get('property'); // test
echo PHP_EOL;
echo $proxy->has('method()'); // true
echo PHP_EOL;
echo $proxy->isInitialised('method()'); // true
echo PHP_EOL;
echo $proxy->get('method()'); // foo
echo PHP_EOL;

$proxy->set('property', 'baz');

echo $object->property; // baz
