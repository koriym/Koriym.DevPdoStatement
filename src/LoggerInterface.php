<?php

declare(strict_types=1);

namespace Koriym\DevPdoStatement;

interface LoggerInterface
{
    /**
     * @param string                           $query
     * @param string                           $time
     * @param array<int, array<string, mixed>> $explain
     * @param array<int, array<string, mixed>> $warnings
     *
     * @return void
     */
    public function logQuery($query, $time, array $explain, array $warnings);
}
