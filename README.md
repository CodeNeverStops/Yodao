# Yodao
A Lazy Dao for mysql(plan:pgsql and mssql).

## Usage

### Init
```php
include 'Yodao.php';
$dao = new Yodao\DB("mysql:dbname=$dbname;host=$host", $user, $password);
```
