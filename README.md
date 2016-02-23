# A PdoStatement for Devloper

** WIP **

With given PSR3 logger, `DevPdoStatement` log PDO query excution time and final SQL query, with parameter values interpolated into it from prepared statement.

It also store explain data detail into given log database.
 
## Usage

Set `DevPdoStatement` class to target `PDO`. Then your `$pdo` starts to return `DevPdoStatement` instead of original `PDOStatement`.

```
use Koriym\DevPdoStatement\DevPdoStatement;
use Koriym\DevPdoStatement\Logger as DbLogger;

$pdo->setAttribute(
    \PDO::ATTR_STATEMENT_CLASS,
    [DevPdoStatement::class, [$pdo, new DbLogger, $logDb]]
);
```

Excution log: 

```
//time: 1.4066696166992E-5 query: insert into user(name) values ('koriym')
//time: 5.9604644775391E-6 query: select name from user where id = 1
```

Expalin log in explain log db:

```
//Array
//(
//    [0] => Array
//    (
//        [time] => 1.4066696166992E-5
//            [query] => insert into user(name) values (:name)
//            [explain] => [
//    {
```


# Demo

```
php doc/demo/run.php 
```