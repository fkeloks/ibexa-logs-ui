<?php

namespace IbexaLogsUi\Bundle\Parser;

use Exception;
use Symfony\Component\VarDumper\Cloner\VarCloner;

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

            $cloner = new VarCloner();

            return [
                'date' => $matches['date'],
                'logger' => $matches['logger'],
                'level' => $matches['level'],
                'message' => $matches['message'],
                'context' => $cloner->cloneVar(json_decode($matches['context'], true)),
                'extra' => $cloner->cloneVar(json_decode($matches['extra'], true)),
            ];
        } catch (Exception $exception) {
            return [];
        }
    }
}
