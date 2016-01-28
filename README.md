# Yodao
A Lazy Dao for mysql(plan:pgsql and mssql).

## Example

### database and table
```bash
mysql -h127.0.0.1 -uroot
use yodao;
desc users;
```
```
+-------------+------------------+------+-----+---------+----------------+
| Field       | Type             | Null | Key | Default | Extra          |
+-------------+------------------+------+-----+---------+----------------+
| id          | int(11)          | NO   | PRI | NULL    | auto_increment |
| name        | varchar(50)      | NO   |     | NULL    |                |
| age         | int(10) unsigned | NO   |     | NULL    |                |
| create_time | int(10) unsigned | NO   |     | NULL    |                |
+-------------+------------------+------+-----+---------+----------------+
4 rows in set (0.01 sec)
```

### Init
```php
include 'Yodao.php';
$dao = new Yodao\DB("mysql:dbname=yodao;host=127.0.0.1", 'root', '');
```

### Specify a table
```php
$tblDao = $dao->table('users');
```

### Select
```php
$ret = $tblDao->select('*', 'name=:name', ['name' => 'youwei']);
```

```php
$ret = $tblDao->select(['id', 'name', 'age'], 'name=:name', ['name' => 'youwei']);
```

### Select One
```php
$ret = $tblDao->selectOne('*', 'name=:name', ['name' => 'youwei']);
```

```php
$ret = $tblDao->selectOne(['id', 'name', 'age'], 'name=:name', ['name' => 'youwei']);
```

### Insert 
```php
$insertId = $tblDao->insert(
    [
        'name' => 'test user',
        'age' => 20,
        'create_time' => time()
    ]
);
```

### Update
```php
$tblDao->update(
    [
        'name' => 'updated user',
        'age' => 30,
    ],
    'id=:id',
    ['id' => $insertId]
);
```

### Insert Or update
```php
$ret = $tblDao->insertOrUpdate(
    [ // insert fields
        'id' => $insertId, 
        'name' => 'new user',
        'age' => 10,
        'create_time' => time(),
    ],
    [ // update fields if the row is duplicated
        'age' => 11,
    ]
);

### Delete rows
```php
$tblDao->delete('id=:id or name=:name', ['id' => 100, 'name' => 'new user']);
```

### Insert From Select
```php
```
