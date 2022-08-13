<?php

namespace IbexaLogsUi\Bundle\Controller;

use eZ\Publish\Core\MVC\Symfony\Security\Authorization\Attribute;
use IbexaLogsUi\Bundle\LogManager\LogFile;
use IbexaLogsUi\Bundle\LogManager\LogTrunkCache;
use EzSystems\EzPlatformAdminUiBundle\Controller\Controller;
use Monolog\Handler\HandlerInterface;
use Monolog\Logger;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\HttpFoundation\Response;

class LogsManagerController extends Controller
{
    /** @var int */
    public static $PER_PAGE_LOGS = 200;

    /** @var int */
    public static $MAX_LOGS = 10000;

    /** @var string */
    private $kernelCacheDir;

    /** @var Logger */
    private $monologLogger;

    public function __construct(string $kernelCacheDir, Logger $monologLogger)
    {
        $this->kernelCacheDir = $kernelCacheDir;
        $this->monologLogger = $monologLogger;
    }

    public function index(int $chunkId = 1): Response
    {
        $this->denyAccessUnlessGranted(new Attribute('ibexa_logs_ui', 'view'));

        $logPaths = $this->getLogPaths();
        $logPath = reset($logPaths);

        if (!is_string($logPath) || !file_exists($logPath)) {
            return $this->render('@ezdesign/logs/logs.html.twig', [
                'logPath' => $logPath,
                'currentChunkId' => $chunkId,
                'perPageLogs' => self::$PER_PAGE_LOGS,
                'total' => null,
                'logs' => []
            ]);
        }

        $logFile = new LogFile($logPath);
        $logTrunkCache = new LogTrunkCache($logPath, $this->kernelCacheDir, 'ibexa_logs_ui');

        /** @var CacheItem $totalCacheItem */
        $totalCacheItem = $logTrunkCache->getCacheSystem()->getItem($logTrunkCache->getCacheKey('total'));
        $total = $totalCacheItem->isHit() ? $totalCacheItem->get() : 0;

        if ($chunkId >= 2 && $chunkId > ceil($total / self::$PER_PAGE_LOGS)) {
            $chunkId = 1;
        }

        if (!$logTrunkCache->hasChunk($chunkId)) {
            $lines = $logFile->tail(self::$MAX_LOGS);

            if (!empty($lines)) {
                $total = count($lines);
                $logTrunkCache->getCacheSystem()->save($totalCacheItem->set($total)->expiresAfter(300));

                foreach (array_chunk($lines, self::$PER_PAGE_LOGS) as $index => $chunk) {
                    $logTrunkCache->setChunk($index + 1, $chunk);
                }

                $logs = array_slice($logFile->parse($lines), 0, self::$PER_PAGE_LOGS);
            }
        } else {
            $lines = $logTrunkCache->getChunk($chunkId);
            $logs = $logFile->parse($lines);
        }

        $logs = array_reduce($logs ?? [], static function (array $carry, array $log) {
            $carry[$log['level']][] = $log;

            return $carry;
        }, []);

        return $this->render('@ezdesign/logs/logs.html.twig', [
            'logPath' => $logPath,
            'currentChunkId' => $chunkId,
            'perPageLogs' => self::$PER_PAGE_LOGS,
            'total' => $total ?? 0,
            'logs' => $logs
        ]);
    }

    public function reload(): Response
    {
        $this->denyAccessUnlessGranted(new Attribute('ibexa_logs_ui', 'view'));

        $logPaths = $this->getLogPaths();
        $logPath = reset($logPaths);

        if (is_string($logPath) && file_exists($logPath)) {
            $logFile = new LogFile($logPath);
            $logTrunkCache = new LogTrunkCache($logPath, $this->kernelCacheDir, 'ibexa_logs_ui');

            $lines = $logFile->tail(self::$MAX_LOGS);

            if (!empty($lines)) {
                /** @var CacheItem $oldTotalCacheItem */
                $oldTotalCacheItem = $logTrunkCache->getCacheSystem()->getItem($logTrunkCache->getCacheKey('total'));
                if ($oldTotalCacheItem->isHit() && $oldTotalCacheItem->get()) {
                    $logTrunkCache->clearChunks($oldTotalCacheItem->get());
                }

                $total = count($lines);
                $logTrunkCache->getCacheSystem()->save($oldTotalCacheItem->set($total)->expiresAfter(300));

                foreach (array_chunk($lines, self::$PER_PAGE_LOGS) as $index => $chunk) {
                    $logTrunkCache->setChunk($index + 1, $chunk);
                }
            }
        }

        return $this->redirectToRoute('ibexa_logs_ui_index');
    }

    private function getLogPaths(): array
    {
        return array_map(static function (HandlerInterface $handler) {
            return method_exists($handler, 'getUrl') ? $handler->getUrl() : $handler->getHandler()->getUrl();
        }, array_filter($this->monologLogger->getHandlers(), static function (HandlerInterface $handler) {
            return
                method_exists($handler, 'getUrl') ||
                (
                    method_exists($handler, 'getHandler') &&
                    method_exists($handler->getHandler(), 'getUrl')
                );
        }));
    }
}
