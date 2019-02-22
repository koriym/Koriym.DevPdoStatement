<?php

declare(strict_types=1);

namespace Koriym\DevPdoStatement;

use PHPUnit\Framework\TestCase;

class DevPdoStatementTest extends TestCase
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

    protected function setUp():void
    {
        parent::setUp();
        $this->pdo = new \PDO('mysql:host=localhost;', 'root');
        $this->logger = new Logger;
        $this->pdo->exec('CREATE DATABASE IF NOT EXISTS tmp;');
        $this->pdo->exec('USE tmp;');
        $this->pdo->exec('CREATE TABLE user(id integer, name text)');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(\PDO::ATTR_STATEMENT_CLASS, [DevPdoStatement::class, [$this->pdo, $this->logger]]);
    }

    protected function tearDown():void
    {
        parent::tearDown();
        $this->pdo->exec('DROP DATABASE tmp;');
    }

    public function testInterpolateQuery()
    {
        $this->pdo->setAttribute(\PDO::ATTR_STATEMENT_CLASS, [DevPdoStatement::class, [$this->pdo, $this->logger]]);
        $sth = $this->pdo->prepare('INSERT INTO user(id, name) VALUES (:id, :name)');
        $this->assertInstanceOf('\Koriym\DevPdoStatement\DevPdoStatement', $sth);
    }

    public function testBindValue()
    {
        $sth = $this->pdo->prepare('INSERT INTO user(id, name) VALUES (:id, :name)');
        $sth->bindValue(':id', 1, \PDO::PARAM_INT);
        $sth->bindValue(':name', 'koriym', \PDO::PARAM_STR);
        $sth->execute();
        $this->assertSame("INSERT INTO user(id, name) VALUES (1, 'koriym')", $sth->interpolateQuery);
    }

    public function testBindParam()
    {
        $sth = $this->pdo->prepare('INSERT INTO user(id, name) VALUES (:id, :name)');
        $id = $name  = '';
        $sth->bindParam(':id', $id, \PDO::PARAM_STR);
        $sth->bindParam(':name', $name, \PDO::PARAM_STR);
        $id = 1;
        $name = 'koriym';
        $sth->execute();
        $this->assertSame("INSERT INTO user(id, name) VALUES (1, 'koriym')", $sth->interpolateQuery);
    }

    public function testExplain()
    {
        $sth = $this->pdo->prepare('INSERT INTO user(id, name) VALUES (:id, :name)');
        $sth->bindParam(':id', $id, \PDO::PARAM_INT);
        $sth->bindParam(':name', $name, \PDO::PARAM_STR);
        for ($i = 0; $i < 100; $i++) {
            $id = $i;
            $name = 'koriym' . (string)$i;
            $sth->execute();
        }
        $this->sth = $this->pdo->prepare('select id, name from user where id > :id');
        $this->sth->bindValue(':id', 80, \PDO::PARAM_INT);
        $this->sth->execute();
        $this->sth->fetchAll(\PDO::FETCH_ASSOC);
        $this->logger;
        $expected = 'SIMPLE';
        $this->assertSame($this->logger->explain[0]['select_type'], $expected);

        return $this->logger->warnings;
    }

    /**
     * @param array $warnings
     *
     * @depends testExplain
     */
    public function testWarnings(array $warnings)
    {
        $this->markTestIncomplete('This test has not been implemented yet.');

        $this->assertNotSame([], $warnings[0]);
        $this->assertArrayHasKey('Level', $warnings[0]);
        $this->assertArrayHasKey('Code', $warnings[0]);
        $this->assertArrayHasKey('Message', $warnings[0]); // /* select#1 */ select NULL AS `name` from `tmp`.`user` where multiple equal(1, NULL)
    }
}
