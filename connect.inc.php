<?php
// BDD
$host="";
$user="";
$bdd=$user;
$pass=""; 

if (!mysql_pconnect($host, $user, $pass))
	mysql_pconnect($host2, $user2, $pass2) or die("connect error ".mysql_error());

mysql_select_db($bdd) or die("select db errpr ".mysql_error());	

function do_mysql_query($query, $silence = 0)
{
	//echo "<br>Request is : <font color='orange'>$query</font><br>";
	$res=mysql_query($query);
	if (!$res & $silence == 0)
		echo mysql_error()."<br> Request was : ".$query."<br>";
	return $res;
}

?>
