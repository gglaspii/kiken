<?php
require("session.php");
require("class.php");
require("process.php");

$acc = new KAccount($sess_account_id);

process_http_post($acc, $_POST);
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>KiKen ! - Tag Manager</title>
<script>
function submit_form(tag_id, tag_name, action)
{
	document.getElementById("hidden_id").value = tag_id;
	document.getElementById("hidden_name").value = tag_name;
	document.getElementById("hidden_action").value = action;
	document.getElementById("f_tag_manage").submit();
}
</script>
</head>
<body>
<a href="main.php"><< Return</a>
<form name="f_tag_manage" id="f_tag_manage" method="POST">
<input type='hidden' name='id' id='hidden_id' value=''>
<input type='hidden' name='name' id='hidden_name' value=''>
<input type='hidden' name='action' id='hidden_action' value=''>
<table>
<tr><td><input type='text' name='add_name' size='10' value=''></td><td><input name='add_tag' type='submit' value='Add'/></td></tr>
<tr><td>&nbsp;</td><td>&nbsp;</td></tr>
<?php
	while($row = $acc->get_tags()) 
	{
		$id = $row["id"];
		$name = $row["name"];
		echo "<tr><td>
		<input type='text' size='10' id='name_$id' value='$name'>
		</td><td>
		<input type='button' value='Update' onClick='submit_form(\"$id\",document.getElementById(\"name_$id\").value,\"update_tag\")'>
		<input type='button' value='Remove' onClick='submit_form(\"$id\",\"$name\",\"remove_tag\")'>
		</td></tr>";
	}
?>
</table>
</form>
</body>
</html>