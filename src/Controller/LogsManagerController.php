<?php

namespace IbexaLogsUi\Bundle\Controller;

use Ibexa\Core\MVC\Symfony\Security\Authorization\Attribute;
use IbexaLogsUi\Bundle\LogManager\LogFile;
use IbexaLogsUi\Bundle\LogManager\LogsCache;
use Ibexa\Contracts\AdminUi\Controller\Controller;
use Monolog\Handler\HandlerInterface;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Response;

class LogsManagerController extends Controller
{
    public const PER_PAGE_LOGS = 200;
    public const MAX_LOGS = 3000;

    /** @var string */
    private $kernelCacheDir;

    /** @var Logger */
    private $monologLogger;

    public function __construct(string $kernelCacheDir, Logger $monologLogger)
    {
        $this->kernelCacheDir = $kernelCacheDir;
        $this->monologLogger = $monologLogger;
    }

    public function index(string $level = 'all', int $page = 1): Response
    {
        if ($level === 'reload') {
            return $this->reload();
        }

        $this->denyAccessUnlessGranted(new Attribute('ibexa_logs_ui', 'view'));

        $logPaths = $this->getLogPaths();
        $logPath = reset($logPaths);

        if (!is_string($logPath) || !file_exists($logPath)) {
            return $this->renderLogs($logPath, $level, $page);
        }

        $logFile = new LogFile($logPath);
        $logsCache = new LogsCache($logPath, $this->kernelCacheDir);

        $logs = $logsCache->get(static function () use ($logFile) {
            $lines = $logFile->tail(self::MAX_LOGS);

            return $logFile->parse($lines);
        });

        // Sort available levels
        $logLevels = array_unique(array_column($logs, 'level'));
        $logLevels = array_intersect(array_keys(LogFile::LOG_LEVELS), $logLevels);

        // Filter by level
        if ($level !== 'all') {
            $filter = mb_strtoupper($level);
            $logs = array_filter($logs, static function (array $log) use ($filter) {
                return $log['level'] === $filter;
            });
        }

        // Empty logs for current level
        if (empty($logs)) {
            return $this->renderLogs($logPath, $level, $page);
        }

        $total = count($logs);
        $logs = array_chunk($logs, 200);

        // Invalid page number
        if (!array_key_exists($page - 1, $logs)) {
            return $this->renderLogs($logPath, $level, $page, $total, $logs[0], $logLevels);
        }

        return $this->renderLogs($logPath, $level, $page, $total, $logs[$page - 1], $logLevels);
    }

    public function reload(): Response
    {
        $this->denyAccessUnlessGranted(new Attribute('ibexa_logs_ui', 'view'));

        $logPaths = $this->getLogPaths();
        $logPath = reset($logPaths);

        if (is_string($logPath) && file_exists($logPath)) {
            $logsCache = new LogsCache($logPath, $this->kernelCacheDir);
            $logsCache->clear();
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

    private function renderLogs(
        string $logPath,
        string $level,
        int $page,
        int $total = 0,
        array $logs = [],
        array $logLevels = LogFile::LOG_LEVELS
    ): Response {
        return $this->render('@ibexadesign/logs/logs.html.twig', [
            'log_path' => $logPath,
            'level' => $level,
            'page' => $page,
            'total' => $total,
            'logs' => $logs,
            'log_levels' => $logLevels
        ]);
    }
}
