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

namespace WebFu\Proxy\Tests\TestData;

class SimpleClass
{
    public string $public = 'public';
    public string $notInitialised;

    public function publicMethod(): void
    {
    }
}
