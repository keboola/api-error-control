<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Tests\Monolog;

use ErrorException;
use Exception;
use Keboola\ErrorControl\Monolog\LogInfo;
use Keboola\ErrorControl\Monolog\LogInfoInterface;
use Keboola\ErrorControl\Monolog\LogProcessor;
use Keboola\ErrorControl\Uploader\UploaderFactory;
use Monolog\DateTimeImmutable;
use Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LogProcessorTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        if (empty(getenv('AWS_S3_LOGS_BUCKET')) || empty(getenv('AWS_DEFAULT_REGION'))) {
            throw new Exception('Environment variable AWS_S3_LOGS_BUCKET or AWS_DEFAULT_REGION is empty.');
        }
    }

    public function testProcessRecordBasic(): void
    {
        $record = [
            'message' => 'test notice',
            'level' => Logger::NOTICE,
            'level_name' => 'NOTICE',
        ];

        $uploaderFactory = new UploaderFactory(
            'https://example.com',
            (string) getenv('AWS_S3_LOGS_BUCKET'),
            (string) getenv('AWS_DEFAULT_REGION')
        );
        $processor = new LogProcessor($uploaderFactory, 'test-app');
        $newRecord = $processor->processRecord($record);
        self::assertCount(10, $newRecord);
        self::assertEquals('test notice', $newRecord['message']);
        self::assertEquals(250, $newRecord['level']);
        self::assertEquals('test-app', $newRecord['app']);
        self::assertGreaterThan(0, $newRecord['pid']);
        self::assertEquals('NOTICE', $newRecord['priority']);
        self::assertEquals([], $newRecord['context']);
        self::assertEquals([], $newRecord['extra']);
        self::assertEquals('NOTICE', $newRecord['level_name']);
        self::assertInstanceOf(DateTimeImmutable::class, $newRecord['datetime']);
        self::assertEquals('', $newRecord['channel']);
    }

    public function testLogProcessorLazyInitUploader(): void
    {
        $record = [
            'message' => 'test notice',
            'level' => Logger::NOTICE,
            'level_name' => 'NOTICE',
        ];

        $uploaderFactory = new UploaderFactory('https://example.com');
        $processor = new LogProcessor($uploaderFactory, 'test-app');
        $newRecord = $processor->processRecord($record);
        self::assertCount(10, $newRecord);
        self::assertEquals('test notice', $newRecord['message']);
        self::assertEquals(250, $newRecord['level']);
        self::assertEquals('test-app', $newRecord['app']);
        self::assertGreaterThan(0, $newRecord['pid']);
        self::assertEquals('NOTICE', $newRecord['priority']);
        self::assertEquals([], $newRecord['context']);
        self::assertEquals([], $newRecord['extra']);
        self::assertEquals('NOTICE', $newRecord['level_name']);
        self::assertInstanceOf(DateTimeImmutable::class, $newRecord['datetime']);
        self::assertEquals('', $newRecord['channel']);
    }

    public function testLogProcessorLazyInitUploaderFail(): void
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

        $uploaderFactory = new UploaderFactory('https://example.com', '', null);
        $processor = new LogProcessor($uploaderFactory, 'test-app');
        $newRecord = $processor->processRecord($record);
        self::assertCount(10, $newRecord);
        self::assertEquals('test exception', $newRecord['message']);
        self::assertEquals(500, $newRecord['level']);
        self::assertEquals('test-app', $newRecord['app']);
        self::assertGreaterThan(0, $newRecord['pid']);
        self::assertEquals('CRITICAL', $newRecord['priority']);
        self::assertArrayHasKey('trace', $newRecord['context']['exception']);
        unset($newRecord['context']['exception']['trace']); // doesn't make sense to test
        self::assertEquals(
            [
                'uploaderError' => 'No uploader can be configured: s3Bucket: "\'\'", s3Region: "NULL", ' .
                    'absConnectionString: "NULL", absContainer: "NULL", path: "NULL".',
                'exceptionId' => '12345',
                'exception' => [
                    'message' => 'exception message',
                    'code' => '543',
                ],
            ],
            $newRecord['context']
        );
        self::assertEquals([], $newRecord['extra']);
        self::assertEquals('CRITICAL', $newRecord['level_name']);
        self::assertInstanceOf(DateTimeImmutable::class, $newRecord['datetime']);
        self::assertEquals('', $newRecord['channel']);
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

        $uploaderFactory = new UploaderFactory(
            'https://example.com',
            (string) getenv('AWS_S3_LOGS_BUCKET'),
            (string) getenv('AWS_DEFAULT_REGION')
        );
        $processor = new LogProcessor($uploaderFactory, 'test-app');
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
        self::assertCount(15, $newRecord);
        self::assertEquals('test notice', $newRecord['message']);
        self::assertEquals(300, $newRecord['level']);
        self::assertEquals('test-app', $newRecord['app']);
        self::assertGreaterThan(0, $newRecord['pid']);
        self::assertEquals('WARNING', $newRecord['priority']);
        self::assertEquals([], $newRecord['context']);
        self::assertEquals([], $newRecord['extra']);
        self::assertEquals('WARNING', $newRecord['level_name']);
        self::assertInstanceOf(DateTimeImmutable::class, $newRecord['datetime']);
        self::assertEquals('', $newRecord['channel']);
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

        $uploaderFactory = new UploaderFactory(
            'https://example.com',
            (string) getenv('AWS_S3_LOGS_BUCKET'),
            (string) getenv('AWS_DEFAULT_REGION')
        );
        $processor = new LogProcessor($uploaderFactory, 'test-app');
        $newRecord = $processor->processRecord($record);
        self::assertCount(10, $newRecord);
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
        self::assertEquals('CRITICAL', $newRecord['level_name']);
        self::assertInstanceOf(DateTimeImmutable::class, $newRecord['datetime']);
        self::assertEquals('', $newRecord['channel']);
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

        $uploaderFactory = new UploaderFactory(
            'https://example.com',
            'runner-non-existent-bucket',
            (string) getenv('AWS_DEFAULT_REGION')
        );
        $processor = new LogProcessor($uploaderFactory, 'test-app');
        $newRecord = $processor->processRecord($record);
        self::assertCount(10, $newRecord);
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
        self::assertEquals('CRITICAL', $newRecord['level_name']);
        self::assertInstanceOf(DateTimeImmutable::class, $newRecord['datetime']);
        self::assertEquals('', $newRecord['channel']);
    }

    public function testProcessRecordDeprecationException(): void
    {
        $record = [
            'message' => 'test exception',
            'level' => Logger::CRITICAL,
            'level_name' => 'CRITICAL',
            'context' => [
                'exceptionId' => '12345',
                'exception' => new ErrorException('exception message', 543, E_USER_DEPRECATED),
            ],
        ];

        $uploaderFactory = new UploaderFactory('https://example.com');
        $processor = new LogProcessor($uploaderFactory, 'test-app');
        $newRecord = $processor->processRecord($record);

        self::assertArrayNotHasKey('attachment', $newRecord['context']);
        self::assertArrayNotHasKey('uploaderError', $newRecord['context']);

        self::assertSame('test exception', $newRecord['message']);
        self::assertSame(500, $newRecord['level']);
        self::assertSame('test-app', $newRecord['app']);
        self::assertGreaterThan(0, $newRecord['pid']);
        self::assertSame('CRITICAL', $newRecord['priority']);
        self::assertSame('12345', $newRecord['context']['exceptionId']);
        self::assertCount(3, $newRecord['context']['exception']);
        self::assertSame('exception message', $newRecord['context']['exception']['message']);
        self::assertSame(543, $newRecord['context']['exception']['code']);
        self::assertArrayHasKey('trace', $newRecord['context']['exception']);
        self::assertSame([], $newRecord['extra']);
        self::assertSame('CRITICAL', $newRecord['level_name']);
        self::assertInstanceOf(DateTimeImmutable::class, $newRecord['datetime']);
        self::assertSame('', $newRecord['channel']);
    }

    public function testLogProcessorOnlyUsesLogInfoInterface(): void
    {
        $record = [
            'message' => 'test notice',
            'level' => Logger::WARNING,
            'level_name' => 'WARNING',
        ];

        /** @var MockObject&UploaderFactory $uploaderFactory */
        $uploaderFactory = self::getMockBuilder(UploaderFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $processor = new LogProcessor($uploaderFactory, 'test-app');
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
        self::assertCount(11, $newRecord);
        self::assertEquals('test notice', $newRecord['message']);
        self::assertEquals(300, $newRecord['level']);
        self::assertEquals('will be added', $newRecord['level_description']);
        self::assertEquals('test-app', $newRecord['app']);
        self::assertGreaterThan(0, $newRecord['pid']);
        self::assertEquals('WARNING', $newRecord['priority']);
        self::assertEquals([], $newRecord['context']);
        self::assertEquals([], $newRecord['extra']);
        self::assertEquals('WARNING', $newRecord['level_name']);
        self::assertInstanceOf(DateTimeImmutable::class, $newRecord['datetime']);
        self::assertEquals('', $newRecord['channel']);
    }
}
