<?php

$mysql_hostname = "localhost";
$mysql_user = "python";
$mysql_password = "python";
$mysql_database = "wordpress";
 
$bd = mysql_connect($mysql_hostname, $mysql_user, $mysql_password) or die("db connect error");
mysql_select_db($mysql_database, $bd) or die("db connect error");
  
?>
