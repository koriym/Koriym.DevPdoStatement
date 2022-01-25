# A PdoStatement for Developers

[![Continuous Integration](https://github.com/koriym/Koriym.DevPdoStatement/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/koriym/Koriym.DevPdoStatement/actions/workflows/continuous-integration.yml)

[[Japanese](README.ja.md)]

[koriym/dev-pdo-statement](https://packagist.org/packages/koriym/dev-pdo-statement) log following information to help your sql inspection.

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

Then `$pdo` start to log as following on each query.

```
time:0.00035190582275391 query: INSERT INTO user(id, name) VALUES (99, 'koriym99')
time:0.00020503997802734 query: SELECT id, name FROM user where id > 80
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

You can implement custom condition for logging or choose your favorite logger.

```php
use Koriym\DevPdoStatement\LoggerInterface;

class MyPsr3Logger implements LoggerInterface
{
    /**
     * {@inheritdoc}
     */
    public function logQuery($query, $time, array $explain, array $warnings)
    {
        // log or throw exception in your custom condition.
    }
}
```


# Demo

```php
php doc/demo/run.php 
```
