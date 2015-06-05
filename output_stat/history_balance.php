<?php
require("../session.php");
require("../class.php");

$acc = new KAccount($sess_account_id);
$date1 = $_GET["date1"];
$date2 = $_GET["date2"];
$without_tags = $_GET["without_tags"];

$acc->get_account_balance_history($date1, $date2, $without_tags);

?>
