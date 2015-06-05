<?php
require("../session.php");
require("../class.php");

$acc = new KAccount($sess_account_id);
$at_date = $_GET["at_date"];

echo $acc->get_solde_at($at_date);

?>
