# A PdoStatement for Developers

[[English](README.md)]

[koriym/dev-pdo-statement](https://packagist.org/packages/koriym/dev-pdo-statement) はSQLクエリーやデータベース運用を改善するために以下の情報を記録します。

 * クエリー実行時間 
 * プリペアードステートメントとバインドされた値を合成したSQL
 * `EXPLAIN`の結果
 * `SHOW WARNINGS`の結果
 
## インストール


`$pdo`に`DevPdoStatement`クラスをセットして[PDO](http://php.net/manual/ja/intro.pdo.php)のプリペアドステートメントクラス`PDOStatement`を置き換え、ログ機能を有効にします。

```php
use Koriym\DevPdoStatement\DevPdoStatement;
use Koriym\DevPdoStatement\Logger;

$pdo->setAttribute(\PDO::ATTR_STATEMENT_CLASS, [DevPdoStatement::class, [$pdo, new Logger]]);
```

これ以降プリペアードステートメントのクエリー行うと以下のようにログされるようになります。

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


## カスタムログ

独自の条件やログ実装を持つロガーを渡す事ができます。

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

一定時間以上の時間がかかったクエリーや`$explain`、`$warnings`で異常があったクエリーの記録や通知が可能です。

```php
$pdo->setAttribute(\PDO::ATTR_STATEMENT_CLASS, [DevPdoStatement::class, [$pdo, new MyPsr3Logger]]);
```


# Demo

```php
php doc/demo/run.php 
```
