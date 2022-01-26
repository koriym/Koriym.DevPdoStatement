# A PdoStatement for Developers

[![Build Status](https://travis-ci.org/koriym/Koriym.DevPdoStatement.svg?branch=1.x)](https://travis-ci.org/koriym/Koriym.DevPdoStatement)

[[English](README.md)]

実行速度の遅いSQLや適切なインデックスが貼られていないクエリー実行をどのように検知していますか？

[koriym/dev-pdo-statement](https://packagist.org/packages/koriym/dev-pdo-statement) はSQLクエリーやデータベース運用を改善するために以下の情報を記録します。

 * クエリー実行時間 
 * プリペアードステートメントとバインドされた値を合成したSQL
 * `EXPLAIN`の実行結果
 * `SHOW WARNINGS`の実行結果
 
## インストール

`$pdo`の属性をセットして [PDO](http://php.net/manual/ja/intro.pdo.php) のプリペアドステートメントクラス`PDOStatement` を`DevPdoStatement`に置き換えます。

```php
use Koriym\DevPdoStatement\DevPdoStatement;
use Koriym\DevPdoStatement\Logger;

$pdo->setAttribute(\PDO::ATTR_STATEMENT_CLASS, [DevPdoStatement::class, [$pdo, new Logger]]);
```

これ以降プリペアードステートメントのクエリー行うと以下のようにログされます。

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


## カスタムログ

独自のロガーを実装して実行時間や`$explain`、`$warnings`で異常が検知されたクエリーだけを記録することが出来ます。

```php
use Koriym\DevPdoStatement\LoggerInterface;

class MyPsr3Logger implements LoggerInterface
{
    /**
     * {@inheritdoc}
     */
    public function logQuery($query, $time, array $explain, array $warnings)
    {
        // 特定条件でログまたは例外を投げる
    }
}
```


```php
$pdo->setAttribute(\PDO::ATTR_STATEMENT_CLASS, [DevPdoStatement::class, [$pdo, new MyPsr3Logger]]);
```


# Demo

```php
php doc/demo/run.php 
```
