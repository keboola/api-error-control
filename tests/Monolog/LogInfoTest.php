<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Tests\Monolog;

use Keboola\ErrorControl\Monolog\LogInfo;
use PHPUnit\Framework\TestCase;

class LogInfoTest extends TestCase
{
    public function testConstruct() : void
    {
        $logInfo = new LogInfo(
            'runId',
            'componentId',
            'projectId',
            'projectName',
            'tokenId',
            'tokenDescription',
            'uri',
            'clientIp'
        );
        self::assertEquals('runId', $logInfo->getRunId());
        self::assertEquals('componentId', $logInfo->getComponentId());
        self::assertEquals('projectId', $logInfo->getProjectId());
        self::assertEquals('projectName', $logInfo->getProjectName());
        self::assertEquals('tokenId', $logInfo->getTokenId());
        self::assertEquals('tokenDescription', $logInfo->getTokenDescription());
        self::assertEquals('uri', $logInfo->getUri());
        self::assertEquals('clientIp', $logInfo->getClientIp());
        self::assertEquals('N/A', $logInfo->getUserAgent());
    }

    public function testUserAgent() : void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'my-other-ua';
        $logInfo = new LogInfo('', '', '', '', '', '', '', '');
        self::assertEquals('my-other-ua', $logInfo->getUserAgent());
        $_SERVER['HTTP_X_USER_AGENT'] = 'my-ua';
        $logInfo = new LogInfo('', '', '', '', '', '', '', '');
        self::assertEquals('my-ua', $logInfo->getUserAgent());
    }
}
