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
     * @var array
     */
    public $explain;

    public function logQuery($query, $time, array $explain)
    {
        $log = sprintf('time:%s query: %s', $query, $time);
        error_log($log);
        $this->explain = $explain;
    }
}
