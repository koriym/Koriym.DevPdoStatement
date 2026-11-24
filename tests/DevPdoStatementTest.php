<?php

declare(strict_types=1);

namespace Koriym\DevPdoStatement;

use PDO;
use PDOException;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

class DevPdoStatementTest extends TestCase
{
    private PDO $pdo;
    private Logger $logger;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            $this->pdo = new PDO('mysql:host=127.0.0.1', 'root', '');
        } catch (PDOException $e) {
            $this->markTestSkipped('MySQL is not available: ' . $e->getMessage());
        }

        $this->logger = new Logger();
        $this->pdo->exec('CREATE DATABASE IF NOT EXISTS tmp;');
        $this->pdo->exec('USE tmp;');
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS user(id integer, name text)');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_STATEMENT_CLASS, [DevPdoStatement::class, [$this->pdo, $this->logger]]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (! isset($this->pdo)) {
            return;
        }

        $this->pdo->exec('DROP DATABASE tmp;');
    }

    private function prepare(string $query): DevPdoStatement
    {
        $sth = $this->pdo->prepare($query);
        $this->assertInstanceOf(DevPdoStatement::class, $sth);

        return $sth;
    }

    public function testInterpolateQuery(): void
    {
        $this->pdo->setAttribute(PDO::ATTR_STATEMENT_CLASS, [DevPdoStatement::class, [$this->pdo, $this->logger]]);
        $sth = $this->pdo->prepare('INSERT INTO user(id, name) VALUES (:id, :name)');
        $this->assertInstanceOf(DevPdoStatement::class, $sth);
    }

    public function testBindValue(): void
    {
        $sth = $this->prepare('INSERT INTO user(id, name) VALUES (:id, :name)');
        $sth->bindValue(':id', 1, PDO::PARAM_INT);
        $sth->bindValue(':name', 'koriym', PDO::PARAM_STR);
        $sth->execute();
        $this->assertSame("INSERT INTO user(id, name) VALUES (1, 'koriym')", $sth->interpolateQuery);
    }

    public function testBindParam(): void
    {
        $sth = $this->prepare('INSERT INTO user(id, name) VALUES (:id, :name)');
        $id = $name  = '';
        $sth->bindParam(':id', $id, PDO::PARAM_STR);
        $sth->bindParam(':name', $name, PDO::PARAM_STR);
        $id = 1;
        $name = 'koriym';
        $sth->execute();
        $this->assertSame("INSERT INTO user(id, name) VALUES (1, 'koriym')", $sth->interpolateQuery);
    }

    /** @return array<mixed> */
    public function testExplain(): array
    {
        $sth = $this->prepare('INSERT INTO user(id, name) VALUES (:id, :name)');
        $sth->bindParam(':id', $id, PDO::PARAM_INT);
        $sth->bindParam(':name', $name, PDO::PARAM_STR);
        for ($i = 0; $i < 100; $i++) {
            $id = $i;
            $name = 'koriym' . (string) $i;
            $sth->execute();
        }

        $select = $this->prepare('select id, name from user where id > :id');
        $select->bindValue(':id', 80, PDO::PARAM_INT);
        $select->execute();
        $select->fetchAll(PDO::FETCH_ASSOC);
        $expected = 'SIMPLE';
        $this->assertSame($expected, $this->logger->explain[0]['select_type']);

        return $this->logger->warnings;
    }

    /** @param array<mixed> $warnings */
    #[Depends('testExplain')]
    public function testWarnings(array $warnings): void
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
        $this->assertNotSame([], $warnings[0]);
        $this->assertArrayHasKey('Level', $warnings[0]);
        $this->assertArrayHasKey('Code', $warnings[0]);
        $this->assertArrayHasKey('Message', $warnings[0]); // /* select#1 */ select NULL AS `name` from `tmp`.`user` where multiple equal(1, NULL)
    }
}
