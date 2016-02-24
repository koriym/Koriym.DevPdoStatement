# A PdoStatement for Developers

[koriym/dev-pdo-statement](https://packagist.org/packages/koriym/dev-pdo-statement) log following information on each SQL query.

 * Query excution time.
 * Final SQL query with parameter values interpolated into it from prepared statement.
 * The result of `EXPLAIN` query.
 * The result of `SHOW WARNINGS` query.

## Install

Attach`DevPdoStatement`class to the target `$pdo`.

```php
use Koriym\DevPdoStatement\DevPdoStatement;
use Koriym\DevPdoStatement\Logger;

$pdo->setAttribute(\PDO::ATTR_STATEMENT_CLASS, [DevPdoStatement::class, [$pdo, new Logger]]);
```

Then `$pdo` start to log each query as following.

```
time:INSERT INTO user(id, name) VALUES (98, 'koriym98') query: 0.00022602081298828
time:INSERT INTO user(id, name) VALUES (99, 'koriym99') query: 0.00022697448730469
time:select id, name from user where id > 80 query: 0.00020599365234375
warnings:[
    {
        "Level": "Note",
        "Code": "1003",
        "Message": "\/* select#1 *\/ select `tmp`.`user`.`id` AS `id`,`tmp`.`user`.`name` AS `name` from `tmp`.`user` where (`tmp`.`user`.`id` > 80)"
    }
]
explain :[
    {
        "id": "1",
        "select_type": "SIMPLE",
        "table": "user",
        "partitions": null,
        "type": "ALL",
        "possible_keys": null,
        "key": null,
        "key_len": null,
        "ref": null,
        "rows": "100",
        "filtered": "33.33",
        "Extra": "Using where"
    }
]
```


## Custom Log

You can implement custom logger in certain condition like over excution time or log with PS3 logger.

```php
use Koriym\DevPdoStatement\LoggerInterface;

class MyPsr3Logger implements LoggerInterface
{
    /**
     * {@inheritdoc}
     */
    public function logQuery($query, $time, array $explain, array $warnings)
    {
        // log by your costum condition
    }
}
```


# Demo

```php
php doc/demo/run.php 
```
