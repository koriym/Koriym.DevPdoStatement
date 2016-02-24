<?php
/**
 * This file is part of the Koriym.DevPdoStatement
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace Koriym\DevPdoStatement;

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
        $log = sprintf('time:%s query: %s', $query, $time);
        error_log($log);
        $this->explain = $explain;
        $this->warnings = $warnings;
    }
}
