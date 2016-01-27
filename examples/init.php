<?php
include 'Yodao.php';

$host = '127.0.0.1';
$dbname = 'yodao';
$dao = new Yodao\DB("mysql:dbname=$dbname;host=$host", 'root', '');
$ret = $dao->table('users')->selectOne('*', 'name=:name', ['name' => 'youwei']);
var_dump($ret);
