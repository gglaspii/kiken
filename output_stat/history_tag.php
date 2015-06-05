<?php
require("../session.php");
require("../class.php");

$acc = new KAccount($sess_account_id);
$tag_name = $_GET["tag_name"];
$month_first_date = $_GET["month_first_date"];
$on_last_month_number = $_GET["on_last_month_number"];

$acc->get_tag_line_chart_highchart($tag_name, $month_first_date, $on_last_month_number);

?>
