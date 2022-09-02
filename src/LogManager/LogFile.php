<?php

namespace IbexaLogsUi\Bundle\LogManager;

use IbexaLogsUi\Bundle\Controller\LogsManagerController;
use IbexaLogsUi\Bundle\Parser\LineLogParser;

class LogFile
{
    /** @var array Log levels for Bootstrap classes */
    public const LOG_LEVELS = [
        'DEBUG' => 'secondary',
        'INFO' => 'info',
        'NOTICE' => 'info',
        'WARNING' => 'warning',
        'ERROR' => 'danger',
        'CRITICAL' => 'danger',
        'ALERT' => 'danger',
        'EMERGENCY' => 'danger'
    ];

    private $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * Read lines from current log file
     * Inspired by the work of Ain Tohvri
     *
     * @see http://tekkie.flashbit.net/php/tail-functionality-in-php
     *
     * @param int $lines
     * @param bool $skipEmptyLines
     *
     * @return array
     */
    public function tail(int $lines = 100, bool $skipEmptyLines = true): array
    {
        $handle = fopen($this->filePath, 'rb');
        $lineCounter = $lines;
        $beginning = false;
        $text = [];
        $pos = -2;

        while ($lineCounter > 0) {
            $t = ' ';
            while ($t !== "\n") {
                if (fseek($handle, $pos, SEEK_END) === -1) {
                    $beginning = true;
                    break;
                }

                $t = fgetc($handle);
                $pos--;
            }

            $lineCounter--;
            if ($beginning) {
                rewind($handle);
            }

            $line = fgets($handle);
            if (trim($line)) {
                $text[$lines - $lineCounter - 1] = $line;
            } elseif ($skipEmptyLines && $lineCounter < ($lines + LogsManagerController::PER_PAGE_LOGS)) {
                $lineCounter++;
            }


            if ($beginning) {
                break;
            }
        }

        fclose($handle);

        return array_map('trim', $text);
    }

    /**
     * Parse log line
     *
     * @param array $lines
     *
     * @return array
     */
    public function parse(array $lines): array
    {
        $lines = array_map(static function ($log) {
            $log = (new LineLogParser)->parse($log);

            if (array_key_exists('level', $log) && array_key_exists($log['level'], self::LOG_LEVELS)) {
                $log['class'] = self::LOG_LEVELS[$log['level']];
            }

            return $log;
        }, $lines);

        return array_filter($lines, 'count');
    }
}
