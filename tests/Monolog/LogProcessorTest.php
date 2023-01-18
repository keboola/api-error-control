<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Tests\Monolog;

use Exception;
use Keboola\ErrorControl\Monolog\LogInfo;
use Keboola\ErrorControl\Monolog\LogInfoInterface;
use Keboola\ErrorControl\Monolog\LogProcessor;
use Monolog\DateTimeImmutable;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class LogProcessorTest extends TestCase
{
    public function testProcessRecordBasic(): void
    {
        $record = [
            'message' => 'test notice',
            'level' => Logger::NOTICE,
            'level_name' => 'NOTICE',
        ];

        $processor = new LogProcessor('test-app');
        $newRecord = (array) $processor($record);
        self::assertCount(8, $newRecord);
        self::assertEquals('test notice', $newRecord['message']);
        self::assertEquals(250, $newRecord['level']);
        self::assertEquals('test-app', $newRecord['app']);
        self::assertGreaterThan(0, $newRecord['pid']);
        self::assertEquals('NOTICE', $newRecord['priority']);
        self::assertEquals([], $newRecord['context']);
        self::assertEquals([], $newRecord['extra']);
        self::assertEquals('NOTICE', $newRecord['level_name']);
    }

    public function testProcessRecordLogInfo(): void
    {
        unset($_SERVER['HTTP_USER_AGENT']);
        unset($_SERVER['HTTP_X_USER_AGENT']);
        $record = [
            'message' => 'test notice',
            'level' => Logger::WARNING,
            'level_name' => 'WARNING',
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
        self::assertCount(13, $newRecord);
        self::assertEquals('test notice', $newRecord['message']);
        self::assertEquals(300, $newRecord['level']);
        self::assertEquals('test-app', $newRecord['app']);
        self::assertGreaterThan(0, $newRecord['pid']);
        self::assertEquals('WARNING', $newRecord['priority']);
        self::assertEquals([], $newRecord['context']);
        self::assertEquals([], $newRecord['extra']);
        self::assertEquals('WARNING', $newRecord['level_name']);
        self::assertEquals('keboola.docker-demo-sync', $newRecord['component']);
        self::assertEquals('12345678', $newRecord['runId']);
        self::assertCount(3, $newRecord['http']);
        self::assertEquals('http://example.com', $newRecord['http']['url']);
        self::assertEquals('123.123.123.123', $newRecord['http']['ip']);
        self::assertEquals('N/A', $newRecord['http']['userAgent']);
        self::assertCount(2, $newRecord['token']);
        self::assertEquals('12345', $newRecord['token']['id']);
        self::assertEquals('My Token', $newRecord['token']['description']);
        self::assertCount(2, $newRecord['owner']);
        self::assertEquals('123', $newRecord['owner']['id']);
        self::assertEquals('My Project', $newRecord['owner']['name']);
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
        ];
        $processor = new LogProcessor('test-app');
        $newRecord = (array) $processor($record);
        self::assertCount(8, $newRecord);
        self::assertEquals('test exception', $newRecord['message']);
        self::assertEquals(500, $newRecord['level']);
        self::assertEquals('test-app', $newRecord['app']);
        self::assertGreaterThan(0, $newRecord['pid']);
        self::assertEquals('CRITICAL', $newRecord['priority']);
        self::assertCount(2, $newRecord['context']);
        self::assertEquals('12345', $newRecord['context']['exceptionId']);
        self::assertCount(3, $newRecord['context']['exception']);
        self::assertEquals('exception message', $newRecord['context']['exception']['message']);
        self::assertEquals(543, $newRecord['context']['exception']['code']);
        self::assertArrayHasKey('trace', $newRecord['context']['exception']);
        self::assertEquals([], $newRecord['extra']);
        self::assertEquals('CRITICAL', $newRecord['level_name']);
    }

    public function testLogProcessorOnlyUsesLogInfoInterface(): void
    {
        $record = [
            'message' => 'test notice',
            'level' => Logger::WARNING,
            'level_name' => 'WARNING',
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
        self::assertCount(9, $newRecord);
        self::assertEquals('test notice', $newRecord['message']);
        self::assertEquals(300, $newRecord['level']);
        self::assertEquals('will be added', $newRecord['level_description']);
        self::assertEquals('test-app', $newRecord['app']);
        self::assertGreaterThan(0, $newRecord['pid']);
        self::assertEquals('WARNING', $newRecord['priority']);
        self::assertEquals([], $newRecord['context']);
        self::assertEquals([], $newRecord['extra']);
        self::assertEquals('WARNING', $newRecord['level_name']);
    }
}
