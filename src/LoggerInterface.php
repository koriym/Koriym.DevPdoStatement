<?php

declare(strict_types=1);

namespace Koriym\DevPdoStatement;

interface LoggerInterface
{
    /**
     * @param array<int, array<string, mixed>> $explain
     * @param array<int, array<string, mixed>> $warnings
     *
     * @return void
     */
    public function logQuery(string $query, string $time, array $explain, array $warnings): void;
}
