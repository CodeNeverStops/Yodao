<?php
include 'Yodao.php';
$host = '127.0.0.1';
$dbname = 'yodao';
$dao = new Yodao("mysql:dbname=$dbname;host=$host", 'root', '');
$dao->setTable('users');
//$id = $dao->insert(
    //[
        //'name' => 'youwei',
        //'age' => 30,
        //'create_time' => time(),
    //]
//);
//var_export($id);
$ret = $dao->select('*', 'id=:id', ['id' => 1]);
var_export($ret);
