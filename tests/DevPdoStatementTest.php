<?php

namespace Koriym\DevPdoStatement;

class DevPdoStatementTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PDO
     */
    private $pdo;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var DevPdoStatement
     */
    private $sth;

    protected function setUp()
    {
        parent::setUp();
        $this->logDb = new \PDO('sqlite::memory:');
        $this->logDb->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->pdo = new \PDO('mysql:host=localhost', 'root');
        $this->logger = new Logger;
        $this->pdo->setAttribute(\PDO::ATTR_STATEMENT_CLASS, [DevPdoStatement::class, [$this->pdo, $this->logger, $this->logDb]]);
        $this->pdo->exec('DROP DATABASE IF EXISTS dev_pdo_test;');
        $this->pdo->exec('CREATE DATABASE IF NOT EXISTS dev_pdo_test; use dev_pdo_test;');
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS user(id integer primary key, name text)');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    public function testNewInstance()
    {
        $this->sth = $this->pdo->prepare('insert into user(name) values (:name)');
        $this->assertInstanceOf('\Koriym\DevPdoStatement\DevPdoStatement', $this->sth);

        return $this->sth;
    }

    /**
     * @depends testNewInstance
     */
    public function testInterpolateQuery(DevPdoStatement $sth)
    {
        $sth->bindValue(':name', 'koriym', \PDO::PARAM_STR);
        $sth->execute();
        $this->assertSame("insert into user(name) values ('koriym')", $sth->interpolateQuery);
    }

    public function testExplain()
    {
        $this->sth = $this->pdo->prepare('select name from user where id = :id');
        $this->sth->bindValue(':id', 1, \PDO::PARAM_INT);
        $this->sth->execute();
        $this->sth->fetchAll();
        $this->logger;
        $expected = 'SIMPLE';
        $this->assertSame($this->logger->explain[0]['select_type'], $expected);
    }
}
