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
     * @param string                           $query
     * @param string                           $time
     * @param array<int, array<string, mixed>> $explain
     * @param array<int, array<string, mixed>> $warnings
     */
    public function logQuery($query, $time, array $explain, array $warnings)
    {
        $this->explain = $explain;
        $this->warnings = $warnings;
        error_log(sprintf("time:%.6f SQL: %s explain: %s",
            (float) $time,
            $query,
            json_encode($explain)
        ));
    }
}
