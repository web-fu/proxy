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

use PHPUnit\Framework\TestCase;
use WebFu\Proxy\PathNotFoundException;
use WebFu\Proxy\Proxy;
use WebFu\Proxy\Tests\TestData\SimpleClass;
use WebFu\Proxy\UnsupportedOperationException;

/**
 * @coversDefaultClass \WebFu\Proxy\Proxy
 *
 * @group unit
 */
class ProxyTest extends TestCase
{
    /**
     * @covers ::has
     *
     * @dataProvider hasDataProvider
     */
    public function testHas(array|object $element, int|string $key, bool $expected): void
    {
        $wrapper = new Proxy($element);
        $this->assertSame($expected, $wrapper->has($key));
    }

    /**
     * @return iterable<mixed[]>
     */
    public function hasDataProvider(): iterable
    {
        yield 'array.key.int' => [
            'element'  => [1],
            'key'      => 0,
            'expected' => true,
        ];
        yield 'array.key.string' => [
            'element'  => ['foo' => 1],
            'key'      => 'foo',
            'expected' => true,
        ];
        yield 'array.key.not-exist' => [
            'element'  => [],
            'key'      => 0,
            'expected' => false,
        ];
        yield 'class.property.exists' => [
            'element' => new class {
                public string $property;
            },
            'key'      => 'property',
            'expected' => true,
        ];
        yield 'class.property.not-exists' => [
            'element' => new class {
            },
            'key'      => 'property',
            'expected' => false,
        ];
        yield 'class.property.is-not-visible' => [
            'element' => new class {
                /**
                 * @phpstan-ignore-next-line
                 */
                private string $property;
            },
            'key'      => 'property',
            'expected' => false,
        ];
        yield 'class.method.exists' => [
            'element' => new class {
                public function method(): void
                {
                }
            },
            'key'      => 'method()',
            'expected' => true,
        ];
        yield 'class.method.not-exists' => [
            'element' => new class {
            },
            'key'      => 'method()',
            'expected' => false,
        ];
        yield 'class.method.is-not-visible' => [
            'element' => new class {
                /**
                 * @phpstan-ignore-next-line
                 */
                private function method(): void
                {
                }
            },
            'key'      => 'method()',
            'expected' => false,
        ];
    }

    /**
     * @covers ::getKeys
     *
     * @dataProvider getKeysProvider
     */
    public function testGetKeys(array|object $element, array $expected): void
    {
        $wrapper = new Proxy($element);
        $this->assertSame($expected, $wrapper->getKeys());
    }

    /**
     * @return iterable<array{element: mixed[], expected: array<int|string>}>
     */
    public function getKeysProvider(): iterable
    {
        yield 'numeric.keys' => [
            'element'  => [1, 2, 3],
            'expected' => [0, 1, 2],
        ];
        yield 'numeric.keys.starting_with' => [
            'element'  => [3 => 1, 2, 3],
            'expected' => [3, 4, 5],
        ];
        yield 'numeric.keys.sparse' => [
            'element'  => [3 => 1, -12 => 2, 5 => 3],
            'expected' => [3, -12, 5],
        ];
        yield 'literal.keys' => [
            'element'  => ['foo' => 1, 'bar' => true],
            'expected' => ['foo', 'bar'],
        ];
        yield 'mixed.keys' => [
            'element'  => ['foo' => 1, 'bar'],
            'expected' => ['foo', 0],
        ];
        yield 'class.keys' => [
            'element' => new class {
                public string $property = 'foo';

                public function method(): string
                {
                    return 'foo';
                }
            },
            'expected' => ['property', 'method()'],
        ];
    }

    /**
     * @covers ::get
     *
     * @dataProvider getDataProvider
     */
    public function testGet(array|object $element, int|string $key, mixed $expected): void
    {
        $proxy = new Proxy($element);
        $this->assertSame($expected, $proxy->get($key));
    }

    /**
     * @return iterable<array{element: object, key: string, expected: mixed}>
     */
    public function getDataProvider(): iterable
    {
        yield 'array.key.int' => [
            'element'  => [1],
            'key'      => 0,
            'expected' => 1,
        ];
        yield 'array.key.string' => [
            'element'  => ['foo' => 1],
            'key'      => 'foo',
            'expected' => 1,
        ];
        yield 'class.property' => [
            'element' => new class {
                public string $property = 'foo';
            },
            'key'      => 'property',
            'expected' => 'foo',
        ];
        yield 'class.method' => [
            'element' => new class {
                public function method(): string
                {
                    return 'foo';
                }
            },
            'key'      => 'method()',
            'expected' => 'foo',
        ];
    }

    /**
     * @covers ::get
     */
    public function testGetFailsIfKeyDoNotExists(): void
    {
        $element = ['foo' => 'string'];
        $wrapper = new Proxy($element);
        $this->expectException(PathNotFoundException::class);
        $wrapper->get('bar');

        $element = new SimpleClass();
        $wrapper = new Proxy($element);
        $this->expectException(PathNotFoundException::class);
        $wrapper->get('not-exists');
    }

    /**
     * @covers ::get
     */
    public function testGetFailsIfKeyIsPrivate(): void
    {
        $element = new class {
            /**
             * @phpstan-ignore-next-line
             */
            private string $property = 'foo';
        };

        $this->expectException(PathNotFoundException::class);
        $this->expectExceptionMessage('Key `property` not found');

        $proxy = new Proxy($element);
        $proxy->get('property');
    }

    /**
     * @covers ::set
     */
    public function testSet(): void
    {
        $element = ['foo' => 'string'];
        $wrapper = new Proxy($element);
        $wrapper->set('foo', 'new');
        $this->assertSame('new', $element['foo']);

        $element = new SimpleClass();
        $wrapper = new Proxy($element);
        $wrapper->set('public', 'new');
        $this->assertSame('new', $element->public);
    }

    /**
     * @covers ::set
     */
    public function testSetFailsIfKeyDoNotExists(): void
    {
        $element = ['foo' => 'string'];
        $wrapper = new Proxy($element);
        $this->expectException(PathNotFoundException::class);
        $this->expectExceptionMessage('Key `not-exists` not found');
        $wrapper->set('not-exists', 'new');

        $element = new SimpleClass();
        $wrapper = new Proxy($element);
        $this->expectException(PathNotFoundException::class);
        $this->expectExceptionMessage('Key `not-exists` not found');
        $wrapper->set('not-exists', 'new');
    }

    /**
     * @covers ::set
     */
    public function testSetFailsIfKeyIsMethod(): void
    {
        $element = new class {
            public function method(): void
            {
            }
        };

        $this->expectException(UnsupportedOperationException::class);
        $this->expectExceptionMessage('Cannot set a class method');

        $proxy = new Proxy($element);
        $proxy->set('method()', 'bar');
    }

    /**
     * @covers ::isInitialised
     */
    public function testIsInitialised(): void
    {
        $element = [
            'foo' => null,
        ];

        $proxy = new Proxy($element);
        $this->assertFalse($proxy->isInitialised('foo'));

        $element['foo'] = 0;
        $this->assertTrue($proxy->isInitialised('foo'));

        $element = new SimpleClass();
        $proxy   = new Proxy($element);
        $this->assertTrue($proxy->isInitialised('public'));
        $this->assertFalse($proxy->isInitialised('notInitialised'));
    }

    /**
     * @covers ::isInitialised
     */
    public function testIsInitialisedFailsIfNoKey(): void
    {
        $element = ['foo' => 'string'];

        $this->expectException(PathNotFoundException::class);
        $this->expectExceptionMessage('Key `bar` not found');

        $proxy = new Proxy($element);
        $proxy->isInitialised('bar');

        $element = new class {
        };

        $this->expectException(PathNotFoundException::class);
        $this->expectExceptionMessage('Key `foo` not found');

        $proxy = new Proxy($element);
        $proxy->isInitialised('foo');
    }

    /**
     * @covers ::create
     *
     * @dataProvider classWithDynamicPropertiesProvider
     */
    public function testCreate(object $element): void
    {
        $proxy = new Proxy($element);
        $proxy->create('foo', new SimpleClass());

        $this->assertInstanceOf(SimpleClass::class, $element->foo);
    }

    /**
     * @return iterable<array{element: object}>
     */
    public function classWithDynamicPropertiesProvider(): iterable
    {
        yield 'stdClass' => [
            'element' => new stdClass(),
        ];
        yield 'classAllowDynamicProperties' => [
            'element' => new WebFu\Proxy\Tests\TestData\ClassWithAllowDynamicProperties(),
        ];
        yield 'classWithMagicMethods' => [
            'element' => new WebFu\Proxy\Tests\TestData\ClassWithMagicMethods(),
        ];
    }

    /**
     * @covers ::create
     */
    public function testCreateChangesNothingIfPropertyAlreadyExists(): void
    {
        $element              = new stdClass();
        $element->foo         = new SimpleClass();
        $element->foo->public = 'test';

        $proxy = new Proxy($element);

        $proxy->create('foo', SimpleClass::class);

        $expected              = new stdClass();
        $expected->foo         = new SimpleClass();
        $expected->foo->public = 'test';

        $this->assertEquals($expected, $element);
    }

    /**
     * @covers ::create
     */
    public function testCreateFailsIfNoDynamicPropertiesAllowed(): void
    {
        $element = new SimpleClass();

        $proxy = new Proxy($element);

        $this->expectException(UnsupportedOperationException::class);
        $this->expectExceptionMessage('Cannot create a new property');

        $proxy->create('foo', SimpleClass::class);
    }

    /**
     * @covers ::unset
     */
    public function testUnset(): void
    {
        $element = [
            'foo' => 1,
        ];

        $proxy = new Proxy($element);
        $proxy->unset('foo');
        $this->assertArrayNotHasKey('foo', $element);

        $element = new class {
            public string $property = 'foo';
        };

        $proxy = new Proxy($element);
        $proxy->unset('property');

        $this->assertFalse(isset($element->property));
    }

    /**
     * @covers ::unset
     */
    public function testUnsetChangesNothingIfNothingToUnset(): void
    {
        $element = ['bar' => 'baz'];

        $proxy = new Proxy($element);
        $proxy->unset('foo');

        $this->assertEquals(['bar' => 'baz'], $element);

        $element = new SimpleClass();

        $proxy = new Proxy($element);
        $proxy->unset('propertyNotExists');

        $expected = new SimpleClass();

        $this->assertEquals($expected, $element);
    }

    /**
     * @covers ::unset
     */
    public function testUnsetFailsIfKeyIsMethod(): void
    {
        $element = new class {
            public function method(): void
            {
            }
        };

        $proxy = new Proxy($element);

        $this->expectException(UnsupportedOperationException::class);
        $this->expectExceptionMessage('Cannot unset a class method');

        $proxy->unset('method()');
    }
}
