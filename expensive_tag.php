<?php
require("session.php");
require("class.php");

$acc = new KAccount($sess_account_id);
$date1 = $_GET["date1"];
$date2 = $_GET["date2"];

$acc->get_expensive_tags_ext_highchart($date1, $date2);

?>
