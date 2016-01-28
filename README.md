# Yodao
A Lazy Dao for mysql(plan:pgsql and mssql).

## Example

### Database and table
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

### Select all fields
```php
$ret = $tblDao->select('*', 'name=:name', ['name' => 'youwei']);
```

### Select specify fields
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
    [ // update fields
        'name' => 'updated user',
        'age' => 30,
    ],
    'id=:id', // where condition
    ['id' => $insertId] // condition vars binding
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
$tblDao->delete(
    'id=:id or name=:name',  // where conditions
    [ // condition vars binding
        'id' => 100, 
        'name' => 'new user'
    ]
);
```

### Insert From Select
```php
$ret = $tblDao->insertFromSelect(
    [ // fields want to copy
        'name' => 'youwei updated', 
        'age' => '.', // '.' means copy the same value from 'source table'
        'create_time' => '.',
    ],
    'age=:age',
    ['age' => 30],
    '', // source table, use the same table if omit
    2, // limit
    0 // offset
);
```

### Insert Multiple Rows
```php
$tblDao->insertMulti(
    [
        [ // row 1
            'name' => 'youwei1',
            'age' => '10',
        ],
        [ // row 2
            'name' => 'youwei2',
            'age' => '20',
        ],
    ],
    [ // these fields will merge into above every rows.
        'create_time' => time(),
    ]
);
```


