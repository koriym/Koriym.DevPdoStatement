<?php
/**
 * This file is part of the Koriym.DevPdoStatement
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
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

    public function bindValue($parameter, $value, $dataType = \PDO::PARAM_STR)
    {
        $this->params[$parameter] = $value;
        parent::bindValue($parameter, $value, $dataType);
    }

    public function bindParam($paramno, &$param, $type = null, $maxlen = null, $driverdata = null)
    {
        $this->params[$paramno] = $param;
        parent::bindParam($paramno, $param, $type = null, $maxlen = null, $driverdata = null);
    }

    public function execute($bountInputParameters = null)
    {
        $start = microtime(true);
        parent::execute($bountInputParameters);
        $time = microtime(true) - $start;
        $this->interpolateQuery = $this->interpolateQuery($this->queryString, $this->params);
        $explain = $this->getExplain($this->interpolateQuery);
        $this->logger->logQuery($this->interpolateQuery, $time, $explain);
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
        $explainSql = sprintf('explain %s', $interpolateQuery);
        try {
            $sth = $this->pdo->query($explainSql);
        } catch (\PDOException $e) {
            return [];
        }
        $explain = $sth->fetchAll(\PDO::FETCH_ASSOC);

        return $explain;
    }
}
