<?php
require("session.php");
require("class.php");
require("process.php");

$acc = new KAccount($sess_account_id);

if (process_http_post($acc, $_POST) == 1)
{
  $status = "OK!";
}
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>KiKen ! - Settings</title>
<script>

</script>
</head>
<body>
<a href="main.php"><< Return</a>
<p><?=$status?></p>
<form name="f_account_settings" id="f_account_settings" method="POST">
<table>
<tr><td>Solde : <input type='text' name='settings_solde' size='10' value="<?=$acc->solde?>"></td><td><input name='settings_update_solde' type='submit' value='Update'/></td></tr>
<tr><td>&nbsp;</td><td>&nbsp;</td></tr>
</table>
</form>
</body>
</html>
