<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Tests;

use Keboola\ErrorControl\Exception\ApplicationException;
use Keboola\ErrorControl\Exception\UserException;
use PHPUnit\Framework\TestCase;

class ExceptionTest extends TestCase
{
    public function testException(): void
    {
        $ex = new ApplicationException('message', 0);
        self::assertInstanceOf(\RuntimeException::class, $ex);
        $ex = new UserException('message', 0);
        self::assertInstanceOf(\RuntimeException::class, $ex);
    }
}
