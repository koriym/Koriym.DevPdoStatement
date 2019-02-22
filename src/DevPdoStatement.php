<?php
/**
 * This file is part of the Koriym.DevPdoStatement
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
declare(strict_types=1);

namespace Koriym\DevPdoStatement;

final class DevPdoStatement extends \PdoStatement
{
    /**
     * Bound parameters
     *
     * @var array
     */
    private $params = [];

    /**
     * Interpolate Query
     *
     * @var string
     */
    public $interpolateQuery;

    /**
     * @var \PDO
     */
    private $pdo;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var
     */
    private $logDb;

    protected function __construct(\PDO $db, LoggerInterface $logger)
    {
        $this->pdo = $db;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function bindValue($parameter, $value, $dataType = \PDO::PARAM_STR)
    {
        $this->params[$parameter] = $value;
        parent::bindValue($parameter, $value, $dataType);
    }

    /**
     * {@inheritdoc}
     */
    public function bindParam($paramno, &$param, $dataType = \PDO::PARAM_STR, $length = null, $driverOptions = null)
    {
        $this->params[$paramno] = &$param;
        parent::bindParam($paramno, $param, $dataType, (int) $length, $driverOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function execute($bountInputParameters = null)
    {
        $start = microtime(true);
        parent::execute($bountInputParameters);
        $time = microtime(true) - $start;
        $this->interpolateQuery = $this->interpolateQuery($this->queryString, $this->params);
        list($explain, $warnings) = $this->getExplain($this->interpolateQuery);
        $this->logger->logQuery($this->interpolateQuery, $time, $explain, $warnings);
    }

    /**
     * Replaces any parameter placeholders in a query with the value of that
     * parameter. Useful for debugging. Assumes anonymous parameters from
     * $params are are in the same order as specified in $query
     *
     * @param string $query  The sql query with parameter placeholders
     * @param array  $params The array of substitution parameters
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
        # build a regular expression for each parameter
        foreach ($params as $key => $value) {
            $keys[] = is_string($key) ? '/' . $key . '/' : '/[?]/';
            if (is_string($value)) {
                $values[$key] = "'" . $value . "'";
            }
            if (is_array($value)) {
                $values[$key] = "'" . implode("','", $value) . "'";
            }
            if (is_null($value)) {
                $values[$key] = 'null';
            }
        }
        $query = preg_replace($keys, $values, $query, 1);

        return $query;
    }

    /**
     * @param string $interpolateQuery
     *
     * @return array
     */
    private function getExplain($interpolateQuery)
    {
        $explainSql = sprintf('EXPLAIN %s', $interpolateQuery);
        try {
            $sth = $this->pdo->query($explainSql);
            $explain = $sth->fetchAll(\PDO::FETCH_ASSOC);
            $sth = $this->pdo->query('SHOW WARNINGS');
            $sth instanceof \PDOStatement ? $warnings = $sth->fetchAll(\PDO::FETCH_ASSOC) : $warnings = [];
        } catch (\PDOException $e) {
            return [[], []];
        }

        return [$explain, $warnings];
    }
}
