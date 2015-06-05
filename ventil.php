<?php
require("session.php");
require("class.php");
require("output.php");

$acc = new KAccount($sess_account_id);
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>KiKen !</title>
<style type="text/css">
<!-- @import url("style.css"); -->
</style>

<script>
function addRowAtTheEnd(tdate,tdesc)
{
var table = document.getElementById("ventil-table");
var row = table.insertRow(-1);
var cell1 = row.insertCell(0);
var cell2 = row.insertCell(1);
var cell3 = row.insertCell(2);
var cell4 = row.insertCell(3);
cell1.innerHTML = "<input type='text' name='tdate[]''  size=10 value='"+tdate+"' onfocus=last_input_tag_focused=0'/>";
cell2.innerHTML = "<input type='text' name='desc[]'' size=50 value='"+tdesc+" - ' onfocus='last_input_tag_focused=0'/>";
cell3.innerHTML = "<input type='text' name='montant[]' size=7 value='-' onfocus='last_input_tag_focused=0'/>";
cell4.innerHTML = "<input type='text' name='tags[]'' value='' onfocus='last_input_tag_focused=this'/>";
}

var last_input_tag_focused=0;
function add_tag(tag_name)
{
	if (last_input_tag_focused)
		last_input_tag_focused.value+=tag_name;
}

</script>

</head>
<body>
<div id="wrap">
<div id="main">
	<a href="main.php"><< Return</a>
	<p> Séparer : </p>
	<table id='box-table-b' border="0" cellpadding="1" cellspacing="1">
		<tr>
			<th>Date</th>
			<th>Description</th>
			<th>Montant</th>
			<th>Tags ","</th>
		</tr>
		<tr>
			<td>
				<input type="text" name="tdate"  size=10 value="<?php echo $_GET["tdate"] ?>" readonly/></td>
			<td>
				<input type="text" name="desc" size=50 value="<?php echo $_GET["tdesc"] ?>" readonly/></td>
			<td>
				<input name="montant" type="text" size=7 value="<?php echo $_GET["amount"] ?>" readonly/></td>
			<td>
				<input name="tags" type="text" value="<?php echo $_GET["tags"] ?>" readonly/></td>
		</tr>
	</table>
		<p> En : </p>
	<button onclick="addRowAtTheEnd('<?php echo $_GET["tdate"] ?>', '<?php echo $_GET["tdesc"] ?>')">Ajouter une ligne</button>
	<form method="post" name="form_ventil_transaction" action="main.php">
	<input type="hidden" name="t_id"  value="<?php echo $_GET["id"] ?>"/>
	<table id='ventil-table' border="0" cellpadding="1" cellspacing="1">
		<tr>
			<th>Date</th>
			<th>Description</th>
			<th>Montant</th>
			<th>Tags ","</th>
		</tr>
		<?php for($i=0;$i<1;$i++) {?>
		<tr>
			<td>
				<input type="text" name="tdate[]"  size=10 value="<?php echo $_GET["tdate"] ?>" onfocus="last_input_tag_focused=0"/></td>
			<td>
				<input type="text" name="desc[]" size=50 value="<?php echo $_GET["tdesc"] ?> - " onfocus="last_input_tag_focused=0"/></td>
			<td>
				<input name="montant[]" type="text" size=7 value="-" onfocus="last_input_tag_focused=0"/></td>
			<td>
				<input name="tags[]" type="text"  value="" onfocus="last_input_tag_focused=this"/></td>
		</tr>
		<?php }?>
	</table>
	<input name="ventil_transaction" type="submit" value="APPLIQUER"/>
	</form>
</div>
	<div id="sidebar">
		<h2>Mes Tags</h2>
		<ul>
			<?php
			while($row = $acc->get_tags()) {
				$name = $row["name"];
				echo "<a href='javascript:void(0)' name='$name' onClick=add_tag(\"$name,\")>&nbsp>&nbsp$name</a><br>";
			}
			?>
		</ul>
	</div>
	</div>

</body>

</html>