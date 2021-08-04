<?php
 
    include("config.php");
  
    $myusername=$_POST['username']; 
	$mypassword=$_POST['password']; 

 
    $sql="SELECT user_login, user_pass, user_email FROM wp_users WHERE user_login='$myusername' and user_pass='$mypassword'";
    $result=mysql_query($sql);

?>
 
<html>
<head>
<meta http-equiv="Content-Type" content="text/html" charset="utf-8"> <!--utf-8설정-->
<title>Login Page</title>
</head>
<?php// echo $sql; ?>
<?php
	while($str = mysql_fetch_array($result)){
		echo "
			$str[user_login],
			$str[user_pass],
			$str[user_email]<br>
		";
	}	
?>
	
</body>
 </html>