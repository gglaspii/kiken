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
	<title>KiKen ! - Agenda</title>
<script>
function submit_form(input_id, input_value, theform)
{
	document.getElementById(input_id).value = input_value;
	theform.submit();
}
</script>
</head>
<body>
<a href="main.php"><< Return</a>

<form method="post" name="form_add_transaction_schedule" action="">
	<table border="0" cellpadding="1" cellspacing="1">
		<tr>
			<th>Next Date</th>
			<th>Type</th>
			<th>Desc</th>
			<th>Montant</th>
			<th>Tags ","</th>
			<th>&nbsp;</th>
		</tr>
		<tr>
			<td>
				<input type="text" name="next_date" id="input_next_date" size=10 value="" /></td>
			<td>
				<select name="type" size="1" id="select_type">
				<option value="1" selected>Mensuel</option>
				<option value="3">Trimestriel</option>
				</select>
			<td>
				<input type="text" name="desc" id ="input_desc"/></td>
			<td>
				<input name="amount" type="text" size=7 id="input_amount" value="-" /></td>
			<td>
				<input name="tags" type="text" id="input_tags"/></td>
			<td><input name="add_transaction_schedule" type="submit" value="Add"/></td>
		</tr>
	</table>
</form>

	<table border="0" cellpadding="1" cellspacing="1">
		<tr>
			<th>Next Date</th>
			<th>Type</th>
			<th>Desc</th>
			<th>Montant</th>
			<th>Tags ","</th>
			<th>&nbsp;</th>
		</tr>
<?php
	while($row = $acc->get_transaction_schedule()) {?>
<form method="post" name="form_update_transaction_schedule" action="" id="form_id_update_transaction_schedule"
onsubmit="return confirm('Sûr ?')";>
		<tr>
			<td>
				<input type="hidden" name="t_id" id ="input_trans_id" value="<?php echo $row["id"]?>"/>
				<input type="text" name="next_date" id="input_next_date" size=10 value="<?php echo $row["next_exec_date"]?>" /></td>
			<td>
				<select name="type" size="1" id="select_type">
				<option value="1" <?if ($row["inc_month"]==1) echo "selected";?>>Mensuel</option>
				<option value="3" <?if ($row["inc_month"]==3) echo "selected";?>>Trimestriel</option>
				</select>
			</td>
			<td>
				<input type="text" name="desc" id ="input_desc" value="<?php echo $row["description"]?>"/></td>
			<td>
				<input name="amount" type="text" size=7 id="input_amount" value="<?php echo $row["amount"]?>" /></td>
			<td>
				<input name="tags" type="text" id="input_tags" value="<?php echo $row["tags"]?>"/></td>
				
			<td>&nbsp;<input name="update_transaction_schedule" id="update_transaction_schedule_id" type="submit" value="Update"/></td>
		<td>&nbsp;<input name="delete_transaction_schedule" id="delete_transaction_schedule_id" type="submit" value="Del"/></td>
		</tr>
</form>
		<?php } ?>
	</table>

</body>
</html>
