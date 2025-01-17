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

namespace WebFu\Proxy\Exception;

use Exception;

class KeyNotFoundException extends Exception
{
    public function __construct(string $key)
    {
        parent::__construct('Key `'.$key.'` not found');
    }
}
