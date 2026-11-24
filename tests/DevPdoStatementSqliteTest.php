<?php

declare(strict_types=1);

namespace Koriym\DevPdoStatement;

use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;
use Stringable;

use function fclose;
use function fopen;
use function get_resource_id;
use function is_numeric;
use function restore_error_handler;
use function set_error_handler;

use const E_WARNING;

class DevPdoStatementSqliteTest extends TestCase
{
    private PDO $pdo;

    /** @var list<array{query: string, time: string, explain: array<int, array<string, mixed>>, warnings: array<int, array<string, mixed>>}> */
    private array $logs = [];

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE user(id integer, name text)');
        $logs = &$this->logs;
        $logger = new class ($logs) implements LoggerInterface {
            /** @param list<array{query: string, time: string, explain: array<int, array<string, mixed>>, warnings: array<int, array<string, mixed>>}> $logs */
            public function __construct(private array &$logs)
            {
            }

            /** @inheritDoc */
            public function logQuery($query, $time, array $explain, array $warnings)
            {
                $this->logs[] = ['query' => $query, 'time' => $time, 'explain' => $explain, 'warnings' => $warnings];
            }
        };
        $this->pdo->setAttribute(PDO::ATTR_STATEMENT_CLASS, [DevPdoStatement::class, [$this->pdo, $logger]]);
    }

    private function prepare(string $query): DevPdoStatement
    {
        $sth = $this->pdo->prepare($query);
        $this->assertInstanceOf(DevPdoStatement::class, $sth);

        return $sth;
    }

    public function testNamedBindValue(): void
    {
        $sth = $this->prepare('INSERT INTO user(id, name) VALUES (:id, :name)');
        $sth->bindValue(':id', 1, PDO::PARAM_INT);
        $sth->bindValue(':name', 'koriym');

        $this->assertTrue($sth->execute());
        $this->assertSame("INSERT INTO user(id, name) VALUES (1, 'koriym')", $sth->interpolateQuery);
        $this->assertSame([['id' => 1, 'name' => 'koriym']], $this->pdo->query('SELECT id, name FROM user')->fetchAll(PDO::FETCH_ASSOC));
    }

    public function testPositionalBindValue(): void
    {
        $sth = $this->prepare('INSERT INTO user(id, name) VALUES (?, ?)');
        $sth->bindValue(1, 2, PDO::PARAM_INT);
        $sth->bindValue(2, 'bear');
        $sth->execute();

        $this->assertSame("INSERT INTO user(id, name) VALUES (2, 'bear')", $sth->interpolateQuery);
    }

    public function testNullAndBoolValues(): void
    {
        $sth = $this->prepare('INSERT INTO user(id, name) VALUES (:id, :name)');
        $sth->bindValue(':id', true, PDO::PARAM_BOOL);
        $sth->bindValue(':name', null, PDO::PARAM_NULL);
        $sth->execute();

        $this->assertSame('INSERT INTO user(id, name) VALUES (1, null)', $sth->interpolateQuery);
    }

    public function testBindParamUsesValueAtExecution(): void
    {
        $sth = $this->prepare('INSERT INTO user(id, name) VALUES (:id, :name)');
        $id = 0;
        $name = '';
        $sth->bindParam(':id', $id, PDO::PARAM_INT);
        $sth->bindParam(':name', $name);
        $id = 3;
        $name = 'sunday';
        $sth->execute();

        $this->assertSame("INSERT INTO user(id, name) VALUES (3, 'sunday')", $sth->interpolateQuery);
    }

    public function testExecuteParametersAreNotInterpolated(): void
    {
        $sth = $this->prepare('INSERT INTO user(id, name) VALUES (:id, :name)');
        $sth->execute([':id' => 4, ':name' => 'ray']);

        $this->assertSame('INSERT INTO user(id, name) VALUES (:id, :name)', $sth->interpolateQuery);
    }

    public function testNonStringValuesUseStringConversion(): void
    {
        $stream = fopen('php://memory', 'r');
        $this->assertIsResource($stream);
        $sth = $this->prepare('SELECT ?, ?, ?, ?');
        $sth->bindValue(1, 1.5);
        $sth->bindValue(2, false, PDO::PARAM_BOOL);
        $sth->bindValue(3, new class implements Stringable {
            public function __toString(): string
            {
                return 'stringable';
            }
        });
        $sth->bindValue(4, $stream, PDO::PARAM_LOB);
        $sth->execute();

        $this->assertSame('SELECT 1.5, , stringable, Resource id #' . get_resource_id($stream), $sth->interpolateQuery);
    }

    public function testArrayAndClosedResourceValues(): void
    {
        $closed = fopen('php://memory', 'r');
        $this->assertIsResource($closed);
        $id = get_resource_id($closed);
        fclose($closed);
        $sth = $this->prepare('SELECT ?, ?, ?, ?');
        // PDO itself warns "Array to string conversion" when it binds an array.
        set_error_handler(static fn (): bool => true, E_WARNING);
        try {
            $sth->bindValue(1, [1, 'a', null, true]);
            $sth->bindValue(2, [[1], 2]);
            $sth->bindValue(3, $closed);
            $sth->bindValue(4, [$closed]);
            $sth->execute();
        } finally {
            restore_error_handler();
        }

        $this->assertSame("SELECT '1','a','','1', 'Array','2', Resource id #{$id}, 'Resource id #{$id}'", $sth->interpolateQuery);
    }

    public function testLogsEachExecution(): void
    {
        $sth = $this->prepare('SELECT id FROM user WHERE id = :id');
        $sth->bindValue(':id', 5, PDO::PARAM_INT);
        $sth->execute();
        $sth->execute();

        $this->assertCount(2, $this->logs);
        $log = $this->logs[0];
        $this->assertSame('SELECT id FROM user WHERE id = 5', $log['query']);
        $this->assertTrue(is_numeric($log['time']));
        // SQLite has no SHOW WARNINGS, so the EXPLAIN result is discarded as well.
        $this->assertSame([], $log['explain']);
        $this->assertSame([], $log['warnings']);
    }

    public function testFailedExecutionIsNotLogged(): void
    {
        $sth = $this->prepare('INSERT INTO user(id, name) VALUES (:id, :name)');
        $this->pdo->exec('DROP TABLE user');
        try {
            $sth->execute([':id' => 1, ':name' => 'x']);
            $this->fail('PDOException expected');
        } catch (PDOException) {
        }

        $this->assertSame([], $this->logs);
        $this->assertSame('', $sth->interpolateQuery);
    }
}
