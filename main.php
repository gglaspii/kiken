<?php
require("session.php");
require("class.php");
require("output.php");
require("process.php");

$acc = new KAccount($sess_account_id);

$acc->check_schedule();
$auto_tagged = $acc->auto_tag_last_transactions();

$fm_date = "Y-m-".$acc->pref_first_month_day;
$fm = $acc->pref_first_month_day;

process_http_post($acc, $_POST);

$date1 = date($fm_date,strtotime("now"));
$date2 = date("Y-m-d",strtotime("$date1 + 1 month - 1 day"));

if (isset($_GET["report_date"]))
{
	$report_date = $_GET["report_date"];
	if ($report_date == '13')		
	{
    	$date1 = "2007-01-01";
		$date2 = date("Y-m-d");
	}
	else
	{
		$date1 = date($report_date);
		$date2 = date('Y-m-d',strtotime("$report_date + 1 month - 1 day"));
	}
}

$graph_type = 0;

if (isset($_POST["hidden_tag_name"]) && $_POST["hidden_tag_name"])
{
	$graph_type = 1;
}
elseif (isset($_POST["tags_inter_stat"]))
{
	$graph_type = 2;
}
elseif (isset($_POST["tags_union_stat"]))
{
	$graph_type = 3;
}
else
{
	$graph_type = 4;
}

?>
<html>
<head>
	<link rel="icon" type="image/png" href="ressource/favicon.png" />
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script type="text/javascript">
<?
if ($graph_type == 1)
{
  $url = "output_stat/history_tag.php?tag_name=".$_POST["hidden_tag_name"]."&month_first_date=$fm&on_last_month_number=12";
}
else if ($graph_type == 4) 
{
  $url = "expensive_tag.php?date1=$date1&date2=$date2";
}
if ($url) {
?>
$(function () {
  var chart;
  $(document).ready(function() {
    $.getJSON("<?=$url?>", function(json) {
      chart = new Highcharts.Chart({
	chart: {
	  renderTo: container_expensive_tags,
	  //plotBackgroundColor: null,
	  //plotBorderWidth: null,
	  //plotShadow: false,
	  type: json.chart.type
	},
	title: json.title,
	subtitle: json.subtitle,
	tooltip: json.tooltip,
	plotOptions: {
	  pie: {
	    //allowPointSelect: true,
	    cursor: 'pointer',
	    dataLabels: {
	      enabled: true,
	      color: '#000000',
	      connectorColor: '#000000',
	      format: '<b>{point.name}</b>: {point.y} €, {point.percentage:.1f} %'
	    }
	  },
	series: {
	    point: {
		events: {
		    click: function (e) {
		      if(this.options.tag_histo)
			go_tag_stat(this.options.tag_histo);
		    }
		}
	    }
	}
	},
	xAxis: {
            categories: json.xAxis
        },
	series: json.series
      });
    });
  });
});
<?}?>

<?if ($graph_type == 4) {?>
	$(function () {
  $(document).ready(function() {
    $.getJSON("output_stat/account_balance.php?date1=<?=$date1?>&date2=<?=$date2?>", function(json) {
      chart = new Highcharts.Chart(json);});});});
<?}?>


        </script>
	<title>KiKen !</title>
<style type="text/css">
<!--
@import url("style.css");
-->
</style>

<script>
function add_tag(tag_name)
{
	document.getElementById("input_tags").value+=tag_name;
}

function updateTransactionFields(tdate, desc, amount, tags, trans_id)
{
	document.getElementById("input_date").value = tdate;
	document.getElementById("input_desc").value = desc;
	document.getElementById("input_amount").value = amount;
	document.getElementById("input_tags").value = tags;
	document.getElementById("input_trans_id").value = trans_id;
	
	show_add_transaction_div();
}

function go_tag_stat(tag_name)
{
	document.getElementById("hidden_id_tag_name").value = tag_name;
	document.getElementById("form_tags_stat").submit();
}

function toggle_div(div_id) {
	var ele = document.getElementById(div_id);
	
	if(ele.style.display == "block") {
    		ele.style.display = "none";
			ele.style.visibility="hidden";
  	}
	else {
		ele.style.display = "block";
		ele.style.visibility="visible";
	}
}

function show_div(div_id)
{
	var ele = document.getElementById(div_id);
	ele.style.display = "block";
	ele.style.visibility="visible";
}

function show_add_transaction_div()
{
	show_div("add_transaction_div");
}

function hide_div(div_id)
{
	var ele = document.getElementById(div_id);
	ele.style.display = "none";
	ele.style.visibility="hidden";
}

function addRow(tableID, desc)
{
 
	var table = document.getElementById(tableID);

	var rowCount = table.rows.length;
	var row = table.insertRow(rowCount);
	
	var d = new Date();
	var	row_id = d.getTime();

	var cell1 = row.insertCell(0);
	
	var elem_hidden = document.createElement("input");
	elem_hidden.type='hidden';
	elem_hidden.id='ventil_hidden_id_'+row_id;
	elem_hidden.name='ventil_hidden_'+row_id;
	elem_hidden.value='0';
	cell1.appendChild(elem_hidden); 
	
	var newlink = document.createElement('a');
	newlink.setAttribute("href", "javascript:document.getElementById('ventil_hidden_id_"+row_id+"').value=1;deleteRow('"+tableID+"')");
	var tn = document.createTextNode('-');
	newlink.appendChild(tn);
	cell1.appendChild(newlink); 
	
	var cell2 = row.insertCell(1);
	var element2 = document.createElement("input");
	element2.type='text';
	element2.name='ventil_desc[]';
	element2.value = desc;
	//element2.size = 10;
	cell2.appendChild(element2); 
	
	var cell3 = row.insertCell(2);
	var element3 = document.createElement("input");
	element3.type='text';
	element3.name='ventil_amount[]';
	cell3.appendChild(element3); 
	
	var cell4 = row.insertCell(3);
	var element4 = document.createElement("input");
	element4.type='text';
	element4.name='ventil_tags[]';
	cell4.appendChild(element4); 
}

function deleteRow(tableID) {
	try {
	var table = document.getElementById(tableID);
	var rowCount = table.rows.length;

	for(var i=0; i<rowCount; i++) {
		var row = table.rows[i];
		var chkbox = row.cells[0].childNodes[0];
		if(null != chkbox && '1' == chkbox.value) {
			table.deleteRow(i);
			rowCount--;
			i--;
		}

	}
	}catch(e) {
		alert(e);
	}
}

</script>
</head>
<body>
<script src="js/highcharts.js"></script>
<script src="js/exporting.js"></script>
<?php

//echo "date1 = $date1, date2 = $date2 <br>";

?>
<div id="wrap">
	<div id="header"><h1>KiKen > <a href="settings.php"><?php print $acc->name ?><a/></h1></div>
	<div id='headerStat' align='center'>
	<?php 
	if ($graph_type == 2)
	{
		$acc->get_multiple_tag_intersect_line_chart($_POST["check_tag"], $fm, 12);
	}
	elseif ($graph_type == 3)
	{
		$acc->get_multiple_tag_union_line_chart($_POST["check_tag"], $fm, 12);
	}
	?>
	</div>
	<div id="container_expensive_tags"></div>
	<div id="nav">
		<ul>
			<li><a href="?">Operations</a></li>
			<li>&nbsp;|&nbsp;<a href="stat.php">Rapports</a></li>
			<li>&nbsp;|&nbsp;<a href="import.php">Importer</a></li>
			<li>&nbsp;|&nbsp;<a href="tag_manage.php">Manage Tags</a></li>
			<li>&nbsp;|&nbsp;<a href="transaction_schedule.php">Agenda</a></li>
            <li>&nbsp;|&nbsp;<a href="tag_auto.php">Tag Auto</a></li>
			<li>&nbsp;|&nbsp;<a href="login.php">Deconnexion</a></li>
		</ul>
	</div>
	<div id="main">
		<h2>Opérations Du <?php echo date("d/m/y", strtotime($date1))?> au <?php echo date("d/m/y", strtotime($date2))?>
		, Solde = 
		<?php 
		echo round($acc->get_solde(0), 2);
		echo "&nbsp;(".round($acc->get_solde(1), 2).")";
		?>
		</h2>

		<div id="container_balance"></div>

        <?if (count($auto_tagged)>0) {
            echo "<p><ul>";
            foreach($auto_tagged as $item)
            {
                echo "<li>auto tag : ".$item["desc"]."</li>";
            }
            echo "</ul></p>";
        }?>
		
		<p><a href="javascript:void(0)" onClick="toggle_div('add_transaction_div')"><img src="ressource/add.png" class="kicon"> Add Transaction</a></p>
		<div id="add_transaction_div" style="visibility:hidden;display:none">
		<form method="post" name="form_add_transaction" action="">
		<a name="transaction_form">
		<table table id='box-table-b' summary='Manage Transactions' border="0" cellpadding="1" cellspacing="1">
			<tr>
				<th>Date</th>
				<th>Description</th>
				<th>Montant</th>
				<th>Tags ","</th>
				<th>&nbsp;</th>
			</tr>
			<tr>
				<td>
					<input type="hidden" name="t_id" id ="input_trans_id"/>
					<input type="text" name="tdate" id="input_date" size=10 value="<?php echo date("Y-m-d");?>" /></td>
				<td>
					<input type="text" name="desc" id ="input_desc"/></td>
				<td>
					<input name="montant" type="text" size=7 id="input_amount" value="-" /></td>
				<td>
					<input name="tags" type="text" id="input_tags"/></td>
				<td><input name="add_transaction" type="submit" value="Add"/>
					<input name="update_transaction" type="submit" value="Update"/></td>
			</tr>
		</table>
	</form>
	</div>
		<p>
		<?php
			$mtab = array("All" => "13");
			for ($i=11; $i >= 0; $i--)
			{
				$mtab[date('F',strtotime("now - $i month"))] = date($fm_date, strtotime("now - $i month") );
			}
			foreach ($mtab as $name => $num)
			{
				echo "<a href='?&report_date=$num'>";
				if (isset($_GET["report_date"]) && $_GET["report_date"] === $num)
					echo "&lt;$name&gt;";
				else
					echo "$name";
				echo "&nbsp;&nbsp;</a>";
			}
		?>
		</p>
		<?php
		if (isset($_POST["hidden_tag_name"]) && $_POST["hidden_tag_name"])
		{
			output_tag_transactions(0, 0, $acc, $_POST["hidden_tag_name"]);
		}
		elseif (isset($_POST["tags_inter_stat"]))
		{
			//$acc->get_multiple_tag_intersect_line_chart($_POST["check_tag"], 4, 12);
			output_multiple_tag_intersect($acc, $_POST["check_tag"]);
		}
		else
		{			
			output_all_transactions($date1, $date2, $acc);
		}
		?>
	</div>
	<div id="sidebar">
		<h2>Mes Tags</h2>
		<ul>
			<form name="tags_stat" action="" method="POST" id="form_tags_stat">
			<?php
			while($row = $acc->get_tags()) {
				$name = $row["name"];
				echo "<input type='checkbox' name='check_tag[]' value='$name' style='vertical-align:middle;'>";
				echo "<a href='javascript:void(0)' name='$name' onClick=add_tag(\"$name,\")>&nbsp<img src='ressource/assign_tag.png' class='kicon'>&nbsp</a><a href='javascript:void(0)' onClick=go_tag_stat(\"$name\")>$name</a><br>";
			}
			?>
			<input type="submit" name="tags_inter_stat" value="Inter">
			<input type="submit" name="tags_union_stat" value="Union">
			<input type="hidden" name="hidden_tag_name" value="" id="hidden_id_tag_name">
			</form>
		</ul>
	</div>
	<div id="footer">
		<p>Footer</p>
	</div>

</div>
</body>
</html>
