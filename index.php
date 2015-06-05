<html>
<head>
<title>Kiken!</title>
</head>
<body>
<?php
 require("camembert/camembert.php"); # on charge la classe camembert
 include("connect.inc.php");
?>

<?php

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



import_csv();
?>


</body>
</html>