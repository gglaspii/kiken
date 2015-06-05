<?php
 include("connect.inc.php");
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=Cp1252">
<title>KiKen - Mes Opérations</title>
</head>
	<table>
	<tr>
		<td>Date</td>
		<td>Description</td>
		<td>Montant</td>
		<td>Tags</td>
	</tr>
	<?php
    //$query = "SELECT transaction.id as tr_id, tdate, description, amount, type, status, GROUP_CONCAT(name SEPARATOR ', ') as tags FROM transaction, transaction_tag, tag WHERE transaction.id = transaction_tag.transaction_id AND transaction_tag.tag_id = tag.id GROUP BY tr_id ORDER BY tdate DESC LIMIT 0,30";
    $query = "SELECT transaction.id as tr_id, tdate, description, amount, type, status, GROUP_CONCAT(name SEPARATOR ', ') as tags FROM (transaction LEFT JOIN transaction_tag ON transaction.id = transaction_tag.transaction_id) LEFT JOIN tag ON transaction_tag.tag_id = tag.id GROUP BY tr_id ORDER BY tdate DESC LIMIT 0,30";
	$res=mysql_query($query );
	while ($row=mysql_fetch_array($res,MYSQL_ASSOC))
	{
		$tr_id = $row["tr_id"];
		$tags = $row["tags"];
		$tdate = $row["tdate"];
		$desc = $row["description"];
		$amount = $row["amount"];
		$type = $row["type"];
	
		echo "<tr><td>$tdate</td>";
		echo "<td>$desc</td>";
		$sign = $type=="Income"?"+":"-";
		echo "<td>$sign $amount</td>";
		echo "<td>$tags</td></tr>";
		
	}
	?>
	</table>
    </body>
</html>