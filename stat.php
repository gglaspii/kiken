<?php
require("session.php");
require("class.php");
require("output.php");
require("process.php");

$acc = new KAccount($sess_account_id);

$fm_date = "Y-m-".$acc->pref_first_month_day;
$fm = $acc->pref_first_month_day;

//process_http_post($acc, $_POST);
$check_tags = $_POST["check_tag"];
$without_tags = "";
if (isset($check_tags))
{
	$without_tags = join("','", array_values($_POST["check_tag"]));
	$without_tags = ",'".$without_tags."'";
}

$date1 = date($fm_date,strtotime("now"));
$date2 = date("Y-m-d",strtotime("$date1 + 1 month - 1 day"));
?>
<html>
<head>
	<link rel="icon" type="image/png" href="ressource/favicon.png" />
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>

<script type="text/javascript">
<?
$url = "output_stat/history_balance.php?date1=$date1&date2=$date2&without_tags=$without_tags";
?>


$(function () {
  $(document).ready(function() {
    $.getJSON("<?=$url?>", function(json) {
      chart = new Highcharts.Chart(json);});});});



</script>
<title>Kiken - Report</title>
<style type="text/css">
<!--
@import url("style.css");
-->
</style>

</head>
<body>
<script src="js/highcharts.js"></script>
<script src="js/exporting.js"></script>

<div id="wrap">
	<div id="header"><h1>KiKen > <a href="settings.php"><?php print $acc->name ?><a/></h1></div>
	<div id="nav">
		<ul>
			<li><a href="main.php">Operations</a></li>
			<li>&nbsp;|&nbsp;<a href="stat.php">Rapports</a></li>
			<li>&nbsp;|&nbsp;<a href="import.php">Importer</a></li>
			<li>&nbsp;|&nbsp;<a href="tag_manage.php">Manage Tags</a></li>
			<li>&nbsp;|&nbsp;<a href="transaction_schedule.php">Agenda</a></li>
            <li>&nbsp;|&nbsp;<a href="tag_auto.php">Tag Auto</a></li>
			<li>&nbsp;|&nbsp;<a href="login.php">Deconnexion</a></li>
		</ul>
	</div>
	<div id="container_history_balance"></div>
	<div id="sidebar">
		<h2>Recalculer sans :</h2>
		<form name="tags_history_balance" action="" method="POST" id="form_tags_history_balance">
		<ul>
		<?
		$tags = $acc->get_account_balance_history_tags($date1, $date2);

		foreach ($tags as $tag) {
			$name = $tag["name"];
			$checked = "";
			$pos = strpos($without_tags, $name);
			if (!($pos === false))
				$checked="checked";
			echo "<input type='checkbox' name='check_tag[]' value='$name' style='vertical-align:middle;' $checked>";
			echo "$name<br>";
		}
		?>
		</ul>
		<br>
		<input type="submit" name="submit_tags_history_balance" value="Refresh">
		</form>
	</div>
	<div id="footer">
		<p>Footer</p>
	</div>
</div>
</body>
</html>
