<?php 
require_once("connect.inc.php"); 
require("process.php");
require("class.php");

function error($msg) {
	echo "$msg : error (".mysql_errno().") ".mysql_error()."<br>";
	return;
}

function mysql_exec($qry)
{
	$res=mysql_query($qry );
	if(!$res)
		error($qry);
}

function import_csv()
{
	$account_id = 2;
	$row = 0;
	$transaction_id = 0;

	setlocale(LC_ALL, 'en_US.UTF-8');

	if (($handle = fopen("ressource/transactions_8.csv", "r")) !== FALSE) {
		mysql_exec("START TRANSACTION;");
		
		while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
			$num = count($data);
			$row++;
			if($row < 3)
				continue;
				
			// 8 column : Date,Description,Currency,Amount,Type,Tags,Account,Status
			$tdate = $data[0];
			$desc = mysql_real_escape_string($data[1]);
			$amount = str_replace(",","",$data[3]);
			$amount = str_replace("+","",$amount);
			$type = $data[4];
			$tag = iconv('UTF-8', 'ISO-8859-1', $data[5]);
			
			$status = $data[7];
			
			/*
			for ($c=0; $c < $num; $c++) {
				echo $data[$c] . "<br />\n";
			}
			*/
			
			if ($type == "Expense")
			{
				$amount = - abs($amount);
				echo "$amount<br>";
			}
			
			$query = "INSERT INTO transaction(account_id, tdate, description, amount, type, status) VALUES($account_id, '$tdate', '$desc', $amount, '$type', '$status')";
			echo "$query <br>";
			$res=mysql_query($query);
			if(!$res)
				error($query);
			else
				$transaction_id = mysql_insert_id();
				
			// for each transaction, insert tags
			$tags = explode(",", $tag);
			foreach ($tags as $a_tag)
			{
				if ($a_tag && $a_tag!="")
				{
					$query = "INSERT INTO tag(name, account_id) values('$a_tag', $account_id)";
					$res=mysql_query($query);
					if(!$res && mysql_errno() != 1062)
						error($query);
				}
				if ($transaction_id)
				{
					$query2 = "insert into transaction_tag(transaction_id, tag_id) select $transaction_id, id from tag where name='$a_tag'";
					//echo "$query2 <br>";
					$res2=mysql_query($query2);
					if(!$res2)
						error($query);
				}
			}
			
			/*
			SELECT *, sum(amount) as tot FROM transaction, transaction_tag, tag WHERE transaction.id = transaction_tag.transaction_id and transaction_tag.tag_id = tag.id group by transaction_tag.tag_id order by tot asc;
			*/
		}
		mysql_exec("COMMIT;");
		fclose($handle);
	}
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=Cp1252">
<title>KiKen - Import</title>
</head>
    <body>
    <a href="main.php"><< Return</a>
	<?php 
	//import_csv();
	$acc = new KAccount(1);
	process_http_post($acc, $_POST);
	?>
	<form action="" method="post">
<select name="datafile" size=5>
<?foreach(glob("ressource"."/*.ofx") as $file) {
$file = str_replace("ressource/", "", $file);
?>
<option value="<?=$file?>" size="30"><?=$file?></option>
<?}?>
</select>

<div>
		<input type="submit" value="Process">
		</div>
		</form>

    </body>

</html>
