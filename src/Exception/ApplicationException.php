<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Exception;

use Keboola\CommonExceptions\ApplicationExceptionInterface;

class ApplicationException extends \RuntimeException implements ApplicationExceptionInterface
{
}
