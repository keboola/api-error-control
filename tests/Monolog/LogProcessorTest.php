<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Tests\Monolog;

use DateTimeImmutable;
use Exception;
use Keboola\ErrorControl\Monolog\LogInfo;
use Keboola\ErrorControl\Monolog\LogInfoInterface;
use Keboola\ErrorControl\Monolog\LogProcessor;
use Monolog\Formatter\JsonFormatter;
use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;

class LogProcessorTest extends TestCase
{
    public function testProcessRecordBasic(): void
    {
        $record = [
            'message' => 'test notice',
            'level' => Logger::NOTICE,
            'level_name' => 'NOTICE',
            'context' => [],
            'channel' => 'test',
            'datetime' => new DateTimeImmutable(),
            'extra' => [],
        ];

        $processor = new LogProcessor('test-app');
        $newRecord = (array) $processor($record);
        self::assertCount(7, $newRecord);
        self::assertEquals('test notice', $newRecord['message']);
        self::assertEquals(250, $newRecord['level']);
        self::assertEquals('test-app', $newRecord['extra']['app']);
        self::assertGreaterThan(0, $newRecord['extra']['pid']);
        self::assertEquals('NOTICE', $newRecord['extra']['priority']);
        self::assertEquals([], $newRecord['context']);
    }

    public function testProcessRecordLogInfo(): void
    {
        unset($_SERVER['HTTP_USER_AGENT']);
        unset($_SERVER['HTTP_X_USER_AGENT']);
        $record = [
            'message' => 'test notice',
            'level' => Logger::WARNING,
            'level_name' => 'WARNING',
            'context' => [],
            'channel' => 'test',
            'datetime' => new DateTimeImmutable(),
            'extra' => [],
        ];
        $processor = new LogProcessor('test-app');
        $processor->setLogInfo(
            new LogInfo(
                '12345678',
                'keboola.docker-demo-sync',
                '123',
                'My Project',
                '12345',
                'My Token',
                'http://example.com',
                '123.123.123.123'
            )
        );
        $newRecord = (array) $processor($record);
        self::assertCount(7, $newRecord);
        self::assertEquals('test notice', $newRecord['message']);
        self::assertEquals(300, $newRecord['level']);
        self::assertEquals('test-app', $newRecord['extra']['app']);
        self::assertGreaterThan(0, $newRecord['extra']['pid']);
        self::assertEquals('WARNING', $newRecord['extra']['priority']);
        self::assertEquals([], $newRecord['context']);
        self::assertEquals('keboola.docker-demo-sync', $newRecord['extra']['component']);
        self::assertEquals('12345678', $newRecord['extra']['runId']);
        self::assertIsArray($newRecord['extra']['http']);
        self::assertCount(3, $newRecord['extra']['http']);
        self::assertEquals('http://example.com', $newRecord['extra']['http']['url']);
        self::assertEquals('123.123.123.123', $newRecord['extra']['http']['ip']);
        self::assertEquals('N/A', $newRecord['extra']['http']['userAgent']);
        self::assertIsArray($newRecord['extra']['token']);
        self::assertCount(2, $newRecord['extra']['token']);
        self::assertEquals('12345', $newRecord['extra']['token']['id']);
        self::assertEquals('My Token', $newRecord['extra']['token']['description']);
        self::assertIsArray($newRecord['extra']['owner']);
        self::assertCount(2, $newRecord['extra']['owner']);
        self::assertEquals('123', $newRecord['extra']['owner']['id']);
        self::assertEquals('My Project', $newRecord['extra']['owner']['name']);
    }

    public function testProcessRecordException(): void
    {
        $record = [
            'message' => 'test exception',
            'level' => Logger::CRITICAL,
            'level_name' => 'CRITICAL',
            'context' => [
                'exceptionId' => '12345',
                'exception' => new Exception('exception message', 543),
            ],
            'channel' => 'test',
            'datetime' => new DateTimeImmutable(),
            'extra' => [],
        ];
        $processor = new LogProcessor('test-app');
        $newRecord = (array) $processor($record);

        self::assertCount(7, $newRecord);
        self::assertEquals('test exception', $newRecord['message']);
        self::assertEquals(500, $newRecord['level']);
        self::assertIsArray($newRecord['extra']);
        self::assertEquals('test-app', $newRecord['extra']['app']);
        self::assertGreaterThan(0, $newRecord['extra']['pid']);
        self::assertEquals('CRITICAL', $newRecord['extra']['priority']);
        self::assertIsArray($newRecord['context']);
        self::assertEquals(
            new Exception('exception message', 543),
            $newRecord['context']['exception']
        );
        self::assertIsArray($newRecord['context']);
        self::assertCount(2, $newRecord['context']);
    }

    public function testProcessRecordExceptionWithFormatter(): void
    {
        self::markTestSkipped('one day possibly');
        /*
        $record = new LogRecord(
            new DateTimeImmutable(),
            'test',
            Level::Info,
            'test',
            [
                'exceptionId' => '12345',
                'exception' => new Exception('exception message', 543),
            ],
            ['channel' => 'test'],
        );
        $formatter = new JsonFormatter();
        $processor = new LogProcessor('test-app');
        $newRecord = $processor($record);
        self::assertInstanceOf(LogRecord::class, $newRecord);
        $formatted = $formatter->format($newRecord);
        self::assertStringContainsString(
            // phpcs:ignore Generic.Files.LineLength
            '{"message":"test","context":{"exceptionId":"12345","exception":{"class":"Exception","message":"exception message","code":543',
            $formatted
        );
        */
    }

    public function testLogProcessorOnlyUsesLogInfoInterface(): void
    {
        $record = [
            'message' => 'test notice',
            'level' => Logger::WARNING,
            'level_name' => 'WARNING',
            'context' => [],
            'channel' => 'test',
            'datetime' => new DateTimeImmutable(),
            'extra' => [],
        ];

        $processor = new LogProcessor('test-app');
        $processor->setLogInfo(
            new class implements LogInfoInterface {
                public function toArray(): array
                {
                    return [
                        'message' => 'will not be overridden',
                        'level_description' => 'will be added',
                    ];
                }
            }
        );
        $newRecord = (array) $processor($record);
        self::assertCount(7, $newRecord);
        self::assertEquals('test notice', $newRecord['message']);
        self::assertEquals(300, $newRecord['level']);
        self::assertEquals('will be added', $newRecord['extra']['level_description']);
        self::assertEquals('test-app', $newRecord['extra']['app']);
        self::assertGreaterThan(0, $newRecord['extra']['pid']);
        self::assertEquals('WARNING', $newRecord['extra']['priority']);
        self::assertEquals([], $newRecord['context']);
    }
}
