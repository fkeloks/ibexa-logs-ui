<?php

namespace IbexaLogsUi\Bundle\LogManager;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\CacheItem;

class LogsCache
{
    /** @var FilesystemAdapter */
    private $cacheSystem;

    /** @var string */
    private $logPath;

    public function __construct(string $logPath, string $cacheDirectory)
    {
        $this->logPath = $logPath;
        $this->cacheSystem = new FilesystemAdapter('ibexa_logs_ui', 0, $cacheDirectory);
    }

    public function get(callable $setter): array
    {
        /** @var CacheItem $cacheItem */
        $cacheItem = $this->cacheSystem->getItem($this->getCacheKey());

        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }

        $value = $setter();

        $cacheItem
            ->set($value)
            ->expiresAfter(300);

        $this->cacheSystem->save($cacheItem);

        return $value;
    }

    public function clear(): bool
    {
        return $this->cacheSystem->deleteItem($this->getCacheKey());
    }

    private function getCacheKey(): string
    {
        return 'ibexa_logs_ui.' . md5($this->logPath);
    }
}
