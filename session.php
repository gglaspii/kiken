<?php
session_start();

require_once("connect.inc.php");

if (!isset($_SESSION['sess_account_id']) && !strstr($_SERVER['PHP_SELF'], "login.php"))
{
	echo "<br>Session Expired<br>";
	echo "<a href='login.php'>Reconnect</a>";
	exit(1);
}

function get_user_id($user_table, $login_column, $passwd_column, $id_column, $login_val, $passwd_val)
{
	$query = "select * from $user_table where $login_column = '$login_val' AND $passwd_column = '$passwd_val'";
	$res=do_mysql_query($query);
	if (!mysql_num_rows($res))
		return 0;
			
	$row = mysql_fetch_array($res,MYSQL_ASSOC);
	
	$id = $row[$id_column];
	
	return $id;
}

if (isset($_SESSION['sess_account_id']) == 0)
{
	$submit_form_name = "login_submit";
	$login_input_name = "login";
	$passwd_input_name = "passwd";
	$user_table = "user";
	$login_column = "login";
	$passwd_column = "passwd";
	$id_column = "id";
	$redirect_on_error = "login.php";
	$redirect_on_success = "main.php";
	
	if(isset($_POST[$submit_form_name]))
	{
		$id = get_user_id($user_table, $login_column, $passwd_column, $id_column, $_POST[$login_input_name], $_POST[$passwd_input_name]);
		if ($id > 0)
		{
			$_SESSION['sess_account_id'] = $id;
			$loc = "Location: $redirect_on_success";
			header($loc);
		}
		else
		{
			echo "not found";
		}
	}
}
else
	$sess_account_id = $_SESSION['sess_account_id'];

?>