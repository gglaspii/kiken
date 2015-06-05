<?php
require("session.php");
require("class.php");
require("process.php");

$acc = new KAccount($sess_account_id);

if (isset($_POST['f_tag_auto_preview_submit']))
{
    process_http_post($acc, $_POST);
}

$ta_id = $_POST["hidden_tag_auto_id_apply"];
$apply_on_type = $_POST["hidden_tag_auto_apply_type"];
$tag_auto_array = $acc->get_auto_tag($ta_id);

$g_regexp = $tag_auto_array["regexp"];
$g_t1_name = $tag_auto_array["t1_name"];
$g_t1_id = $tag_auto_array["t1_id"];
$g_t2_name = $tag_auto_array["t2_name"];
$g_t2_id = $tag_auto_array["t2_id"];
$g_t3_name = $tag_auto_array["t3_name"];
$g_t3_id = $tag_auto_array["t3_id"];
$g_tr_type = $tag_auto_array["tr_type"];
?>


</head>
<body>
<p><a href="tag_auto.php"><< Return Tag Auto</a></p>
<p><a href="main.php"><< Return Main</a></p>
<p>
<?echo "<p>$g_regexp -> $g_t1_name, $g_t2_name, $g_t3_name</p>"?>
<form name="f_tag_auto_preview" id="f_tag_auto_preview" method="POST">
<input type='hidden' name='hidden_tag_auto_id_apply' id='hidden_tag_auto_id_apply' value='<?=$ta_id?>'>
<input type='hidden' name='t1_name' value='<?=$g_t1_name?>'>
<input type='hidden' name='t2_name' value='<?=$g_t2_name?>'>
<input type='hidden' name='t3_name' value='<?=$g_t3_name?>'>
<input type='submit' value='OK, Apply!' name='f_tag_auto_preview_submit'>

</p>
<table>
<?
// walk all acc transaction, check if transaction's desc match regexp
// if so, apply tags !
while($row = $acc->get_transactions('1979-00-00', date('Y-m-d'), 0, 0))
{
	$tr_id = $row["tr_id"];
	$tags = $row["tags"];
	$tdate = $row["tdate"];
	$desc = $row["description"];
    $amount = $row["amount"];

    $pattern = "/$g_regexp/i";
    if (preg_match($pattern, $desc) === 1 && ($apply_on_type == 2 || ($apply_on_type == 1 && strlen($tags)==0)))
    {
        echo "<tr><td>$desc</td><td>$tdate</td><td>$amount</td></tr>"; 
        echo "<input type='hidden' name='tr_auto_tag[]' value='$tr_id'/>";     
    }
}
?>
</table>
</form>
</body>
</html>
