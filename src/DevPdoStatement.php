<?php

declare(strict_types=1);

namespace Koriym\DevPdoStatement;

use Override;
use PDO;
use PDOException;
use PDOStatement;
use Stringable;

use function array_map;
use function get_debug_type;
use function gettype;
use function implode;
use function is_array;
use function is_resource;
use function is_scalar;
use function is_string;
use function microtime;
use function preg_replace;
use function sprintf;

/** @inherits PDOStatement<mixed> PHPStan does not treat PDOStatement as generic; Psalm does. */
final class DevPdoStatement extends PDOStatement
{
    /**
     * Bound parameters
     *
     * @var array<mixed>
     */
    private array $params = [];

    /**
     * Interpolate Query
     */
    public string $interpolateQuery = '';

    protected function __construct(
        private readonly PDO $db,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * {@inheritDoc}
     *
     * @param int|string $param
     * @param mixed      $value
     * @param int        $type
     */
    #[Override]
    public function bindValue($param, $value, $type = PDO::PARAM_STR): bool
    {
        $this->params[$param] = $value;

        return parent::bindValue($param, $value, $type);
    }

    /**
     * {@inheritDoc}
     *
     * @param string|int $param
     * @param mixed      $var
     * @param int        $type
     * @param int|null   $maxLength
     * @param mixed      $driverOptions
     */
    #[Override]
    public function bindParam($param, &$var, $type = PDO::PARAM_STR, $maxLength = null, $driverOptions = null): bool
    {
        $this->params[$param] = &$var;

        return parent::bindParam($param, $var, $type, (int) $maxLength, $driverOptions);
    }

    /**
     * {@inheritDoc}
     *
     * @param array<mixed>|null $params
     */
    #[Override]
    public function execute($params = null): bool
    {
        $start = microtime(true);
        $result = parent::execute($params);
        $time = microtime(true) - $start;
        $this->interpolateQuery = $this->interpolateQuery($this->queryString, $this->params);
        [$explain, $warnings] = $this->getExplain($this->interpolateQuery);
        $this->logger->logQuery($this->interpolateQuery, (string) $time, $explain, $warnings);

        return $result;
    }

    /**
     * Replaces any parameter placeholders in a query with the value of that
     * parameter. Useful for debugging. Assumes anonymous parameters from
     * $params are are in the same order as specified in $query
     *
     * @param string       $query  The sql query with parameter placeholders
     * @param array<mixed> $params The array of substitution parameters
     *
     * @return string The interpolated query
     *
     * @link http://stackoverflow.com/a/8403150
     */
    private function interpolateQuery(string $query, array $params): string
    {
        $keys = [];
        $values = [];
        // build a regular expression for each parameter
        foreach ($params as $key => $value) {
            $keys[] = is_string($key) ? '/' . $key . '/' : '/[?]/';
            $values[$key] = match (true) {
                is_string($value) => "'" . $value . "'",
                is_array($value) => "'" . implode("','", array_map($this->toText(...), $value)) . "'",
                $value === null => 'null',
                default => $this->toText($value),
            };
        }

        return (string) preg_replace($keys, $values, $query, 1);
    }

    /**
     * PHP's string conversion; a nested array becomes 'Array' and an object without
     * __toString() is shown by its class name.
     */
    private function toText(mixed $value): string
    {
        return match (true) {
            is_scalar($value), $value === null, $value instanceof Stringable, is_resource($value) => (string) $value,
            gettype($value) === 'resource (closed)' => 'Resource id #' . (int) $value,
            is_array($value) => 'Array',
            default => get_debug_type($value),
        };
    }

    /** @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, mixed>>} */
    private function getExplain(string $interpolateQuery): array
    {
        $explainSql = sprintf('EXPLAIN %s', $interpolateQuery);
        try {
            $sth = $this->db->query($explainSql);
            if ($sth === false) {
                return [[], []];
            }

            /** @var array<int, array<string, mixed>> $explain */
            $explain = $sth->fetchAll(PDO::FETCH_ASSOC);
            $warningSth = $this->db->query('SHOW WARNINGS');
            if ($warningSth === false) {
                return [[], []];
            }

            /** @var array<int, array<string, mixed>> $warnings */
            $warnings = $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException) {
            return [[], []];
        }

        return [$explain, $warnings];
    }
}
