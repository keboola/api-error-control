<?php

declare(strict_types=1);

namespace Keboola\ErrorControl;

class ExceptionIdGenerator
{
    public static function generateId(): string
    {
        return 'exception-' . md5(microtime());
    }
}
