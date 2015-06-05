<?php
require("session.php");
require("class.php");
require("process.php");

$acc = new KAccount($sess_account_id);

if ($p_array["hidden_tag_auto_id_apply"]>0)
{
}
else
{
    process_http_post($acc, $_POST);
}

$g_tag_array = array();

while($row = $acc->get_tags())
{
    array_push($g_tag_array, $row);
}
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>KiKen ! - Tag Auto</title>
<script>
function submit_form_del(tag_auto_id)
{
	document.getElementById("hidden_tag_auto_id_del").value = tag_auto_id;
	document.getElementById("f_tag_auto_list").submit();
}
function submit_form_apply(tag_auto_id, apply_type)
{
	document.getElementById("hidden_tag_auto_id_apply").value = tag_auto_id;
    document.getElementById("hidden_tag_auto_apply_type").value = apply_type;
	document.getElementById("f_tag_auto_list_apply").submit();
}
</script>
</head>
<body>
<a href="main.php"><< Return</a>
<form name="f_tag_auto_add" id="f_tag_auto_add" method="POST">
<table>
<tr>
<td>Regexp</td>
<td>Tag 1</td>
<td>Tag 2</td>
<td>Tag 3</td>
<td>Type</td>
</tr>
<tr>
<td><input type='text' name='add_name' size='50' value=''></td>
<td><select name="tag_1" size=10>
<option value="0" selected>-</option>
<?foreach($g_tag_array as $row) {?>
<option value="<?=$row["id"]?>"><?=$row["name"]?></option>
<?}?>
</select></td>
<td><select name="tag_2" size=10>
<option value="0" selected>-</option>
<?foreach($g_tag_array as $row) {?>
<option value="<?=$row["id"]?>"><?=$row["name"]?></option>
<?}?>
</select></td>
<td><select name="tag_3" size=10>
<option value="0" selected>-</option>
<?foreach($g_tag_array as $row) {?>
<option value="<?=$row["id"]?>"><?=$row["name"]?></option>
<?}?>
</select></td>
<td><select name="tr_type" size=2>
<option value="Expense" selected>Depense</option>
<option value="Income">Revenu</option>
</select></td>
</tr>
<tr><td>&nbsp;</td><td>&nbsp;</td></tr>
</table>
<input name='add_auto_tag' type='submit' value='Add'/>
</form>

<form name="f_tag_auto_list" id="f_tag_auto_list" method="POST">
<input type='hidden' name='hidden_tag_auto_id_del' id='hidden_tag_auto_id_del' value=''>
</form>
<form name="f_tag_auto_list_apply" id="f_tag_auto_list_apply" method="POST" action="tag_auto_preview.php">
<input type='hidden' name='hidden_tag_auto_id_apply' id ='hidden_tag_auto_id_apply' value=''>
<input type='hidden' name='hidden_tag_auto_apply_type' id='hidden_tag_auto_apply_type' value='0'>
</form>
<p>
<table>
<?php
	while($row = $acc->get_auto_tags())
	{
        $id = $row["id"];
		$regexp = $row["regexp"];
		$t1_id = $row["t1_id"];
        $t1_name = $row["t1_name"];
        $t2_id = $row["t2_id"];
        $t2_name = $row["t2_name"];
        $t3_id = $row["t3_id"];
        $t3_name = $row["t3_name"];
        $t4_id = $row["t4_id"];
        $t4_name = $row["t4_name"];
        $t5_id = $row["t5_id"];
        $t5_name = $row["t5_name"];
        $tr_type = $row["tr_type"];
		echo "<tr><td>
		<input type='text' size='50' id='regexp_$id' value='$regexp'>
		</td>
        <td>
        <input type='hidden' name='t1_id_$id' id='hidden_action' value='$t1_id'>
        <input type='text' size='10' id='t1_name_$id' value='$t1_name'>
        </td>
        <td>
        <input type='hidden' name='t2_id_$id' id='hidden_action' value='$t2_id'>
        <input type='text' size='10' id='t2_name_$id' value='$t2_name'>
        </td>
        <td>
        <input type='hidden' name='t3_id_$id' id='hidden_action' value='$t3_id'>
        <input type='text' size='10' id='t3_name_$id' value='$t3_name'>
        </td>
        <td>
        <input type='text' size='10' id='tr_type_$id' value='$tr_type'>
        </td>
        <td>
        <input type='button' value='Remove' onClick='submit_form_del(\"$id\")'>
        </td>
        <td>
        <input type='button' value='Apply on empty' onClick='submit_form_apply(\"$id\", 1)'>
        </td>
        <td>
        <input type='button' value='Apply on all' onClick='submit_form_apply(\"$id\", 2)'>
        </td>
        </tr>";
	}
?>
</table>
</body>
</html>
