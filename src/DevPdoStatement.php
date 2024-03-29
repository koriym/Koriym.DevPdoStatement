<?php

declare(strict_types=1);

namespace Koriym\DevPdoStatement;

use PDO;
use PDOException;
use PdoStatement;
use ReturnTypeWillChange;

use function implode;
use function is_array;
use function is_string;
use function microtime;
use function preg_replace;
use function sprintf;

final class DevPdoStatement extends PdoStatement
{
    /**
     * Bound parameters
     *
     * @var array<mixed>
     */
    private $params = [];

    /**
     * Interpolate Query
     *
     * @var string
     */
    public $interpolateQuery = '';

    /** @var PDO */
    private $pdo;

    /** @var LoggerInterface */
    private $logger;

    protected function __construct(PDO $db, LoggerInterface $logger)
    {
        $this->pdo = $db;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     *
     * @param int|string $parameter
     * @param int|string $value
     * @param int        $dataType
     */
    #[ReturnTypeWillChange]
    public function bindValue($parameter, $value, $dataType = PDO::PARAM_STR)
    {
        $this->params[$parameter] = $value;

        return parent::bindValue($parameter, $value, $dataType);
    }

    /**
     * {@inheritDoc}
     *
     * @param string|int $paramno
     * @param mixed      $param
     * @param int        $dataType
     * @param int        $length
     * @param mixed      $driverOptions
     */
    #[ReturnTypeWillChange]
    public function bindParam($paramno, &$param, $dataType = PDO::PARAM_STR, $length = null, $driverOptions = null)
    {
        $this->params[$paramno] = &$param;

        return parent::bindParam($paramno, $param, $dataType, (int) $length, $driverOptions);
    }

    /**
     * {@inheritdoc}
     *
     * @param array<mixed> $bountInputParameters
     */
    #[ReturnTypeWillChange]
    public function execute($bountInputParameters = null)
    {
        $start = microtime(true);
        $result = parent::execute($bountInputParameters);
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
     * thanks
     */
    private function interpolateQuery($query, $params)
    {
        $keys = [];
        $values = $params;
        // build a regular expression for each parameter
        foreach ($params as $key => $value) {
            $keys[] = is_string($key) ? '/' . $key . '/' : '/[?]/';
            if (is_string($value)) {
                $values[$key] = "'" . $value . "'";
            }

            if (is_array($value)) {
                $values[$key] = "'" . implode("','", $value) . "'";
            }

            if ($value !== null) {
                continue;
            }

            $values[$key] = 'null';
        }

        $query = preg_replace($keys, $values, $query, 1);

        return (string) $query;
    }

    /**
     * @param string $interpolateQuery
     *
     * @return array{0:array<int, array<string, mixed>>, 1:array<int, array<string, mixed>>}
     */
    private function getExplain($interpolateQuery)
    {
        $explainSql = sprintf('EXPLAIN %s', $interpolateQuery);
        try {
            $sth = $this->pdo->query($explainSql);
            if ($sth === false) {
                return [[], []];
            }

            $explain = $sth->fetchAll(PDO::FETCH_ASSOC);
            $warningSth = $this->pdo->query('SHOW WARNINGS');
            if ($warningSth === false) {
                return [[], []];
            }

            $warnings = $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [[], []];
        }

        /** @var array<int, array<string, mixed>> $explain */
        $explain = $explain ?: [];
        /** @var array<int, array<string, mixed>> $warnings */
        $warnings = $warnings ?: [];

        return [$explain, $warnings];
    }
}
