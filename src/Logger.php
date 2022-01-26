<?php

declare(strict_types=1);

namespace Koriym\DevPdoStatement;

use function error_log;
use function json_encode;
use function sprintf;
use const JSON_PRETTY_PRINT;

final class Logger implements LoggerInterface
{
    /**
     * EXPLAIN
     *
     * @var array<mixed>
     */
    public $explain = [];

    /**
     * SHOW WARNINGS
     *
     * @var array<mixed>
     */
    public $warnings = [];

    /**
     * {@inheritDoc}
     *
     * @param array<int, array<string, mixed>> $explain
     * @param array<int, array<string, mixed>> $warnings
     */
    public function logQuery(string $query, string $time, array $explain, array $warnings): void
    {
        $this->explain = $explain;
        $this->warnings = $warnings;
        if (! $this->warnings) {
            return;
        }
        $level = $this->warnings[0]['Level'] ?? 'n/a';
        $code = $this->warnings[0]['Code'] ?? 'n/a';
        error_log(sprintf(
            'level:%s code:%s time:%.6f message:"%s" explain:%s',
            $level,
            (string) $code,
            (float) $time,
            $query,
            json_encode($explain, JSON_PRETTY_PRINT)
        ));
    }
}
