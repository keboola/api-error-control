<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Monolog;

class LogInfo
{
    /**
     * @var string
     */
    private $clientIp;

    /**
     * @var string
     */
    private $userAgent;

    /**
     * @var string
     */
    private $projectId;

    /**
     * @var string
     */
    private $projectName;

    /**
     * @var string
     */
    private $tokenId;

    /**
     * @var string
     */
    private $tokenDescription;

    /**
     * @var string
     */
    private $uri;

    /**
     * @var string
     */
    private $runId;

    /**
     * @var string
     */
    private $componentId;

    public function __construct(
        string $runId,
        string $componentId,
        string $projectId,
        string $projectName,
        string $tokenId,
        string $tokenDescription,
        string $uri,
        string $clientIp
    ) {
        $this->runId = $runId;
        $this->componentId = $componentId;
        $this->projectId = $projectId;
        $this->projectName = $projectName;
        $this->tokenId = $tokenId;
        $this->tokenDescription = $tokenDescription;
        $this->uri = $uri;
        $this->userAgent = $_SERVER['HTTP_X_USER_AGENT'] ?? $_SERVER['HTTP_USER_AGENT'] ?? 'N/A';
        $this->clientIp = $clientIp;
    }

    public function getClientIp(): string
    {
        return $this->clientIp;
    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    public function getProjectId(): string
    {
        return $this->projectId;
    }

    public function getProjectName(): string
    {
        return $this->projectName;
    }

    public function getTokenId(): string
    {
        return $this->tokenId;
    }

    public function getTokenDescription(): string
    {
        return $this->tokenDescription;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getRunId(): string
    {
        return $this->runId;
    }

    public function getComponentId(): string
    {
        return $this->componentId;
    }
}
