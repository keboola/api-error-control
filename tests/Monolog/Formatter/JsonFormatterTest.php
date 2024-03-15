<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Tests\Monolog\Formatter;

use DateTimeImmutable;
use Exception;
use Keboola\ErrorControl\Monolog\Formatter\JsonFormatter;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;

class JsonFormatterTest extends TestCase
{
    public function testFormattingException(): void
    {
        $logWithException = new LogRecord(
            new DateTimeImmutable(),
            'test',
            Level::Critical,
            'Message',
            [new Exception('Message', previous: new Exception('Previous'))]
        );

        $json = json_decode((new JsonFormatter())->format($logWithException), true);
        assert(is_array($json));

        $context = $json['context'][0];
        self::assertEquals('Exception', $context['class']);
        self::assertEquals('Message', $context['message']);
        self::assertSame(0, $context['code']);
        self::assertMatchesRegularExpression('/^.*\/JsonFormatterTest\.php:\d+/', $context['file']);
        self::assertIsString($context['trace']);
        self::assertNotEmpty($context['trace']);

        $previousContext = $context['previous'];
        self::assertEquals('Exception', $previousContext['class']);
        self::assertEquals('Previous', $previousContext['message']);
        self::assertSame(0, $previousContext['code']);
        self::assertMatchesRegularExpression('/^.*\/JsonFormatterTest\.php:\d+/', $previousContext['file']);
        self::assertIsString($previousContext['trace']);
        self::assertNotEmpty($previousContext['trace']);
    }
}
