<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Monolog\Formatter;

use Monolog\Formatter\JsonFormatter as MonologJsonFormatter;
use Throwable;

class JsonFormatter extends MonologJsonFormatter
{
    public function __construct(
        int $batchMode = MonologJsonFormatter::BATCH_MODE_JSON,
        bool $appendNewline = true,
        bool $ignoreEmptyContextAndExtra = false,
        bool $includeStacktraces = true,
    ) {
        parent::__construct(
            $batchMode,
            $appendNewline,
            $ignoreEmptyContextAndExtra,
            $includeStacktraces,
        );
    }

    protected function normalizeException(Throwable $e, int $depth = 0): array
    {
        $data = parent::normalizeException($e, $depth);

        if ($this->includeStacktraces) {
            $data['trace'] = $e->getTraceAsString();
        }

        return $data;
    }
}
