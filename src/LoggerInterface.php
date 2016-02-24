<?php
/**
 * This file is part of the Koriym.DevPdoStatement
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace Koriym\DevPdoStatement;

interface LoggerInterface
{
    /**
     * @param string $query
     * @param string $time
     * @param array  $explain
     * @param array  $warnings
     */
    public function logQuery($query, $time, array $explain, array $warnings);
}
