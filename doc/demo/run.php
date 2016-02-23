<?php

use Koriym\DevPdoStatement\DevPdoStatement;
use Koriym\DevPdoStatement\Logger as DbLogger;

require dirname(dirname(__DIR__)) . '/vendor/autoload.php';

$logDb = new \PDO('sqlite::memory:');
$logDb->exec('CREATE TABLE explain(time, query, explain)');

$pdo = new \PDO('sqlite::memory:');
$pdo->setAttribute(\PDO::ATTR_STATEMENT_CLASS, [DevPdoStatement::class, [$pdo, new DbLogger, $logDb]]);
$pdo->exec('CREATE TABLE user(id integer primary key, name text)');
$sth = $pdo->prepare('insert into user(name) values (:name)');
$sth->bindValue(':name', 'koriym', \PDO::PARAM_STR);
$sth->execute();

// select
$sth = $pdo->prepare('select name from user where id = :id');
$sth->bindValue(':id', 1, \PDO::PARAM_INT);
$sth->execute();

// dump explain from explain db
$explains = $logDb->query('select * from explain')->fetchAll(\PDO::FETCH_ASSOC);

print_r($explains);

//time: 1.4066696166992E-5 query: insert into user(name) values ('koriym')
//time: 5.9604644775391E-6 query: select name from user where id = 1
//Array
//(
//    [0] => Array
//    (
//        [time] => 1.4066696166992E-5
//            [query] => insert into user(name) values (:name)
//            [explain] => [
//    {
//        "addr": "0",
//        "opcode": "Init",
//        "p1": "0",
//        "p2": "8",
//        "p3": "0",
//        "p4": "",
//        "p5": "00",
//        "comment": null
//    },
// ...

