<?php

namespace IbexaLogsUi\Bundle\Parser;

use Exception;

class LineLogParser
{
    /** @var string */
    private const PARSER_PATTERN = '/^\[(?<date>.*?)\] (?<logger>\w+).(?<level>\w+): (?<message>[^\[\{]+) (?<context>[\[\{].*[\]\}]) (?<extra>[\[\{].*[\]\}])$/';

    /** @var array */
    private const PARSER_GROUPS = ['date', 'logger', 'level', 'message', 'context', 'extra'];

    public function parse(string $log): array
    {
        try {
            if ($log === '') {
                return [];
            }

            $match = preg_match(self::PARSER_PATTERN, $log, $matches);
            if (!$match) {
                return [];
            }

            foreach (self::PARSER_GROUPS as $group) {
                if (!array_key_exists($group, $matches)) {
                    return [];
                }
            }

            // Json extract
            $jsonContext = $matches['context'] === '[]' ? [] : json_decode($matches['context'], true, 2);
            $jsonExtra = $matches['extra'] === '[]' ? [] : json_decode($matches['extra'], true, 2);

            return [
                'date' => $matches['date'],
                'logger' => $matches['logger'],
                'level' => $matches['level'],
                'message' => $matches['message'],
                'context' => !$jsonContext && $jsonContext !== [] ? [$matches['context']] : $jsonContext,
                'extra' => !$jsonExtra && $jsonExtra !== [] ? [$matches['extra']] : $jsonExtra,
            ];
        } catch (Exception $exception) {
            return [];
        }
    }
}
