<?php

require '../librarys/identicon/autoload.php';

$identicon = new \Identicon\Identicon();
$userName = $_GET['userName'];
$identicon->displayImage($userName, 512);
?>