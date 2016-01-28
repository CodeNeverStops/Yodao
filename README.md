# Yodao
A Lazy Dao for mysql(plan:pgsql and mssql).

## Usage

### Init
```php
include 'Yodao.php';
$dao = new Yodao\DB("mysql:dbname=$dbname;host=$host", $user, $password);
```

### Insert One Row
```php
$ret = $dao->table('users')->selectOne('*', 'name=:name', ['name' => 'youwei']);
```

```php
$ret = $dao->table('users')->selectOne(['id', 'name'], 'name=:name', ['name' => 'youwei']);
```
