<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Tests\Monolog;

use Exception;
use Keboola\ErrorControl\Monolog\LogInfo;
use Keboola\ErrorControl\Monolog\LogInfoInterface;
use Keboola\ErrorControl\Monolog\LogProcessor;
use Keboola\ErrorControl\Monolog\S3Uploader;
use Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LogProcessorTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        if (empty(getenv('S3_LOGS_BUCKET')) || empty(getenv('AWS_DEFAULT_REGION'))) {
            throw new Exception('Environment variable S3_LOGS_BUCKET or AWS_DEFAULT_REGION is empty.');
        }
    }

    public function testProcessRecordBasic(): void
    {
        $record = [
            'message' => 'test notice',
            'level' => Logger::NOTICE,
            'level_name' => 'NOTICE',
        ];

        $uploader = new S3Uploader(
            'https://example.com',
            (string) getenv('S3_LOGS_BUCKET'),
            (string) getenv('AWS_DEFAULT_REGION')
        );
        $processor = new LogProcessor($uploader, 'test-app');
        $newRecord = $processor->processRecord($record);
        self::assertCount(7, $newRecord);
        self::assertEquals('test notice', $newRecord['message']);
        self::assertEquals(250, $newRecord['level']);
        self::assertEquals('test-app', $newRecord['app']);
        self::assertGreaterThan(0, $newRecord['pid']);
        self::assertEquals('NOTICE', $newRecord['priority']);
        self::assertEquals([], $newRecord['context']);
        self::assertEquals([], $newRecord['extra']);
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

        $uploader = new S3Uploader(
            'https://example.com',
            (string) getenv('S3_LOGS_BUCKET'),
            (string) getenv('AWS_DEFAULT_REGION')
        );
        $processor = new LogProcessor($uploader, 'test-app');
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
        $newRecord = $processor->processRecord($record);
        self::assertCount(12, $newRecord);
        self::assertEquals('test notice', $newRecord['message']);
        self::assertEquals(300, $newRecord['level']);
        self::assertEquals('test-app', $newRecord['app']);
        self::assertGreaterThan(0, $newRecord['pid']);
        self::assertEquals('WARNING', $newRecord['priority']);
        self::assertEquals([], $newRecord['context']);
        self::assertEquals([], $newRecord['extra']);
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

        $uploader = new S3Uploader(
            'https://example.com',
            (string) getenv('S3_LOGS_BUCKET'),
            (string) getenv('AWS_DEFAULT_REGION')
        );
        $processor = new LogProcessor($uploader, 'test-app');
        $newRecord = $processor->processRecord($record);
        self::assertCount(7, $newRecord);
        self::assertEquals('test exception', $newRecord['message']);
        self::assertEquals(500, $newRecord['level']);
        self::assertEquals('test-app', $newRecord['app']);
        self::assertGreaterThan(0, $newRecord['pid']);
        self::assertEquals('CRITICAL', $newRecord['priority']);
        self::assertCount(3, $newRecord['context']);
        self::assertStringStartsWith('https://example.com', $newRecord['context']['attachment']);
        self::assertEquals('12345', $newRecord['context']['exceptionId']);
        self::assertCount(3, $newRecord['context']['exception']);
        self::assertEquals('exception message', $newRecord['context']['exception']['message']);
        self::assertEquals(543, $newRecord['context']['exception']['code']);
        self::assertArrayHasKey('trace', $newRecord['context']['exception']);
        self::assertEquals([], $newRecord['extra']);
    }

    public function testProcessRecordExceptionBrokenUploader(): void
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

        $uploader = new S3Uploader(
            'https://example.com',
            'runner-non-existent-bucket',
            (string) getenv('AWS_DEFAULT_REGION')
        );
        $processor = new LogProcessor($uploader, 'test-app');
        $newRecord = $processor->processRecord($record);
        self::assertCount(7, $newRecord);
        self::assertEquals('test exception', $newRecord['message']);
        self::assertEquals(500, $newRecord['level']);
        self::assertEquals('test-app', $newRecord['app']);
        self::assertGreaterThan(0, $newRecord['pid']);
        self::assertEquals('CRITICAL', $newRecord['priority']);
        self::assertCount(3, $newRecord['context']);
        self::assertStringContainsString('The specified bucket does not exist', $newRecord['context']['uploaderError']);
        self::assertEquals('12345', $newRecord['context']['exceptionId']);
        self::assertCount(3, $newRecord['context']['exception']);
        self::assertEquals('exception message', $newRecord['context']['exception']['message']);
        self::assertEquals(543, $newRecord['context']['exception']['code']);
        self::assertArrayHasKey('trace', $newRecord['context']['exception']);
        self::assertEquals([], $newRecord['extra']);
    }

    public function testLogProcessorOnlyUsesLogInfoInterface(): void
    {
        $record = [
            'message' => 'test notice',
            'level' => Logger::WARNING,
            'level_name' => 'WARNING',
        ];

        /** @var MockObject&S3Uploader $uploader */
        $uploader = $this->createMock(S3Uploader::class);
        $processor = new LogProcessor($uploader, 'test-app');
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
        $newRecord = $processor->processRecord($record);
        self::assertCount(8, $newRecord);
        self::assertEquals('test notice', $newRecord['message']);
        self::assertEquals(300, $newRecord['level']);
        self::assertEquals('will be added', $newRecord['level_description']);
        self::assertEquals('test-app', $newRecord['app']);
        self::assertGreaterThan(0, $newRecord['pid']);
        self::assertEquals('WARNING', $newRecord['priority']);
        self::assertEquals([], $newRecord['context']);
        self::assertEquals([], $newRecord['extra']);
    }
}
