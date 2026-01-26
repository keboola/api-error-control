<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Exception;

interface CustomCodeExceptionInterface
{
    public function getCustomCode(): ?string;
}
