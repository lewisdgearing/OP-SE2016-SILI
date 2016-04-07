<?php
$host = "localhost";
$userMS = $_SERVER['SILI_DB_USER'];
$passwordMS = $_SERVER['SILI_DB_PASS'];
$database = $_SERVER['SILI_DB'];

$db = new MysqliDb($host, $userMS, $passwordMS, $database);
?>