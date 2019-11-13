<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Monolog;

interface LogInfoInterface
{
    public function toArray(): array;
}
