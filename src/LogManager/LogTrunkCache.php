<?php

namespace IbexaLogsUi\Bundle\LogManager;

use IbexaLogsUi\Bundle\Controller\LogsManagerController;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\CacheItem;

class LogTrunkCache
{
    /** @var FilesystemAdapter */
    private $cacheSystem;

    /** @var string */
    private $logPath;

    /** @var string */
    private $cacheNamespace;

    public function __construct(string $logPath, string $cacheDirectory, string $cacheNamespace = '')
    {
        $this->logPath = $logPath;
        $this->cacheNamespace = $cacheNamespace;
        $this->cacheSystem = new FilesystemAdapter($cacheNamespace, 0, $cacheDirectory);
    }

    public function getCacheKey(string $subject = 'logs'): string
    {
        return $this->cacheNamespace . '.' . $subject . '.' . md5($this->logPath);
    }

    public function getChunkIdentifier(int $chunkId): string
    {
        return $this->getCacheKey() . '.chunk.' . $chunkId;
    }

    public function hasChunk(int $chunkId): bool
    {
        try {
            /** @var CacheItem $cacheItem */
            $cacheItem = $this->cacheSystem->getItem($this->getChunkIdentifier($chunkId));

            return $cacheItem->isHit();
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }

    public function getChunk(int $chunkId, $default = null)
    {
        try {
            /** @var CacheItem $cacheItem */
            $cacheItem = $this->cacheSystem->getItem($this->getChunkIdentifier($chunkId));

            if ($cacheItem->isHit()) {
                return $cacheItem->get();
            }

            return $default;
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }

    public function getLastChunk(int $currentChunkId, int $total, $default = null)
    {
        try {
            $lastChunkCacheKey = $this->getChunkIdentifier(ceil($total / LogsManagerController::$PER_PAGE_LOGS) - ($currentChunkId - 1));

            /** @var CacheItem $cacheItem */
            $cacheItem = $this->cacheSystem->getItem($lastChunkCacheKey);

            if ($cacheItem->isHit()) {
                return $cacheItem->get();
            }

            return $default;
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }

    public function setChunk(int $chunkId, $value, $ttl = 300): bool
    {
        try {
            /** @var CacheItem $cacheItem */
            $cacheItem = $this->cacheSystem->getItem($this->getChunkIdentifier($chunkId));
            $cacheItem->set($value)->expiresAfter($ttl);

            return $this->cacheSystem->save($cacheItem);
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }

    public function clearChunks(int $total): bool
    {
        $numberOfChunks = ceil($total / LogsManagerController::$PER_PAGE_LOGS);
        $chunkCacheKeys = [];

        for ($chunkId = 1; $chunkId <= $numberOfChunks; $chunkId++) {
            $chunkCacheKeys[] = $this->getChunkIdentifier($chunkId);
        }

        try {
            return $this->cacheSystem->deleteItems($chunkCacheKeys);
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }

    public function getCacheSystem(): FilesystemAdapter
    {
        return $this->cacheSystem;
    }
}
