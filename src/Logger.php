<?php

declare(strict_types=1);

namespace Koriym\DevPdoStatement;

use function error_log;
use function json_encode;
use function sprintf;

use const JSON_PRETTY_PRINT;

class Logger implements LoggerInterface
{
    /**
     * EXPLAIN
     *
     * @var array
     */
    public $explain;

    /**
     * SHOW WARNINGS
     *
     * @var array
     */
    public $warnings;

    /**
     * {@inheritdoc}
     */
    public function logQuery($query, $time, array $explain, array $warnings)
    {
        $log = sprintf('time:%s query: %s', $time, $query);
        error_log($log);
        $this->explain = $explain;
        $this->warnings = $warnings;
        if (! $warnings) {
            return;
        }

        error_log('warnings:' . (string) json_encode($warnings, JSON_PRETTY_PRINT));
        error_log('explain :' . (string) json_encode($explain, JSON_PRETTY_PRINT));
    }
}
