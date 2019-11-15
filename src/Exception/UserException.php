<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Exception;

use Keboola\CommonExceptions\UserExceptionInterface;

class UserException extends \RuntimeException implements UserExceptionInterface
{
}
