# A PdoStatement for Developers

[[English](README.md)]

[koriym/dev-pdo-statement](https://packagist.org/packages/koriym/dev-pdo-statement) はSQLクエリーやデータベース運用を改善するために以下の情報を記録します。

 * クエリー実行時間 
 * プリペアードステートメントとバインドされた値を合成したSQL
 * `EXPLAIN`の結果
 * `SHOW WARNINGS`の結果
 
## インストール


`$pdo`に`DevPdoStatement`クラスをセットして[PDO](http://php.net/manual/ja/intro.pdo.php)のプリペアドステートメントクラス`PDOStatement`を置き換えログ機能を有効にします。

```php
use Koriym\DevPdoStatement\DevPdoStatement;
use Koriym\DevPdoStatement\Logger;

$pdo->setAttribute(\PDO::ATTR_STATEMENT_CLASS, [DevPdoStatement::class, [$pdo, new Logger]]);
```

これ以降プリペアードステートメントクエリー行うと以下のようにログされます。

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
        // log by your costum condition
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
