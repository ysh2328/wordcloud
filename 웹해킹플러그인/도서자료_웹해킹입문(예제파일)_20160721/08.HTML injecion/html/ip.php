<?PHP
$ip_address;
$ip_address = $_SERVER['REMOTE_ADDR'];
$port = $_SERVER['SERVER_PORT'];
$agent = $_SERVER['HTTP_USER_AGENT'];
$time = time();

$log = $ip_address." , ".$port." , ".$agent." , ".$time;
$file = './log.txt';
file_put_contents($file, $log .PHP_EOL, FILE_APPEND);
?>
 