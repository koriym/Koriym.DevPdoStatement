# A PdoStatement for Developers

`DevPdoStatement` log PDO query excution time and final SQL query, with parameter values interpolated into it from prepared statement.

It can  also store explain data detail.
 
## Usage

Set `DevPdoStatement` class to target `$pdo`. Then it starts to return `DevPdoStatement` instead of original `PDOStatement` for profiling.

```php
use Koriym\DevPdoStatement\DevPdoStatement;
use Koriym\DevPdoStatement\Logger;

$pdo->setAttribute(
    \PDO::ATTR_STATEMENT_CLASS,
    [DevPdoStatement::class, [$pdo, new Logger]]
);
```

Excution log: 

```
//time: 1.4066696166992E-5 query: insert into user(name) values ('koriym')
//time: 5.9604644775391E-6 query: select name from user where id = 1
```

Expalin log in explain log db:

```php
//Array
//(
//    [0] => Array
//    (
//        [time] => 1.4066696166992E-5
//            [query] => insert into user(name) values (:name)
//            [explain] => [
//    {
```

## Custom Logger

You can have your own logger.

```php
use Koriym\DevPdoStatement\LoggerInterface;

class MyPsr3Logger implements LoggerInterface
{
    public function logQuery($query, $time, array $explain)
    {
        // log with PSR3 logger
    }
}
```
# Demo

```
php doc/demo/run.php 
```
