<script language="javascript">
function delete_tr(id, amount, desc)
{
    if (!confirm("Delete "+amount+"€ ("+desc+") ?"))
        return;
    document.getElementById('hidden_tr_id_to_delete_id').value = id;
    document.getElementById('form_update_tags_id').submit();
}

function solde_at(id, account_id, at_date)
{
  //confirm("Account "+account_id+", Solde au "+date+" = ");
  if (window.XMLHttpRequest) {
      // code for IE7+, Firefox, Chrome, Opera, Safari
      xmlhttp = new XMLHttpRequest();
  } else {
      // code for IE6, IE5
      xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
  }
  xmlhttp.onreadystatechange = function() {
      if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
          ret = xmlhttp.responseText;
          str = "Solde au "+at_date+" = "+ret;
          str = str.replace(/(\r\n|\n|\r)/gm,"");
          confirm(str);
      }
  }
  xmlhttp.open("GET","async/async.php?action=1&account_id="+account_id+"&at_date="+at_date,true);
  xmlhttp.send();
}
</script>
<?php
function output_all_transactions($date1, $date2, &$acc)
{
	$strdate1 = date("d/m/y", strtotime($date1));
	$strdate2 = date("d/m/y", strtotime($date2));
	$title = "Transactions du $strdate1 au $strdate2";
	
	$table = new html_all_transaction_table($title, $acc, $date1, $date2);
	$table->output();
}

function output_tag_transactions($date1, $date2, &$acc, $tag_name)
{
	$strdate1 = date("d/m/y", strtotime($date1));
	$strdate2 = date("d/m/y", strtotime($date2));
	$title = "Transactions pour $tag_name";
	
	$table = new html_tag_transaction_table($title, $acc, $date1, $date2, $tag_name);
	$table->output();	
}

function output_multiple_tag_intersect(&$acc, &$tag_array)
{
	$tagconcatname = implode(", ", $tag_array);
	
	$title = "Transactions intersection pour $tagconcatname";
	
	$table = new html_multiple_tag_transaction_table($title, $acc, "", "", $tag_array, 1);
	$table->output();
	
	return;
}

abstract class html_transaction_table
{
	var $title;
	
	function __construct($title)
	{
		$this->title = $title;
	}
	
	abstract protected function get_transaction_row();
	
	function output()
	{
		echo "<h2>$this->title</h2><p>";
		echo "<form name='form_update_tags' id='form_update_tags_id' method='POST' action=''>";
        echo "<input type='hidden' id='hidden_tr_id_to_delete_id' name='hidden_tr_id_to_delete' value=0>";
		echo "<table id='box-table-a' summary='Transactions'>
		<tr>
		<th>Date</th>
		<th >Description</th>
		<th >Montant</th>
		<th >Tags</th>
		</tr>";
		
		while($row = $this->get_transaction_row())
		{
      $acc_id = $this->acc->get_id();
			$tr_id = $row["tr_id"];
			$tags = $row["tags"];
			$tdate = $row["tdate"];
			$desc = $row["description"];
			$amount = $row["amount"];
			$type = $row["type"];
			$status = $row["status"];
			$split_parent_id = $row["split_parent_id"];
			$split_desc = $row["split_desc"];
			$splitted = $row["splitted"];
			$split_mark="";

			if ($splitted == 1)
				continue;
			
			$htmlstatus = "";
			if ($status === "Reconciled")
				$htmlstatus = "checked";

			if ($split_parent_id) {
				$split_mark = "^";
			}			

			echo "<tr><td width='120'>";
			echo "<input type='hidden' name='hidden_transaction_id[]' value='$tr_id'>";
      echo "<a href='javascript:void(0)' onClick=\"solde_at('$tr_id', '$acc_id', '$tdate')\"><img src='ressource/solde_at.png' class='kicon'></a>";
			echo "<a href='#transaction_form' onClick=\"updateTransactionFields('$tdate', '$desc', '$amount', '$tags', '$tr_id')\"><img src='ressource/edit.png' class='kicon'></a>";
			//echo "<a href='javascript:void(0)' onClick=\"toggle_div('table_ventil_$tr_id');\">+&nbsp;</a>";
            $prefix_desc = substr($desc, 0, 20);
			echo "<a href='javascript:void(0)' onClick=\"delete_tr($tr_id, '$amount', '$prefix_desc');\"><img src='ressource/del.png' class='kicon'></a>";
			echo "&nbsp;&nbsp;$tdate</td>";
			echo "<td><input type='checkbox' name='reconciled[]' value='$tr_id' $htmlstatus style='vertical-align:middle;'>
			&nbsp;<a href='ventil.php?id=$tr_id&tdate=$tdate&tdesc=$desc&amount=$amount&tags=$tags'><img src='ressource/split.png' class='kicon'></a>&nbsp;
			<span title='Transaction parente : $split_desc'>$split_mark</span>&nbsp;
			$desc
			</td>";
			echo "<td>";
			if ($type == "Income") echo "<b><font color='black'>$amount</font></b>";
			else echo "$amount";
			echo "</td>";
			if ($tags)
			{
				echo "<td>$tags</td></tr>";
				echo "<input type='hidden' name='text_update_tags[]' value='' size='15'>";
			}
			else
			{
				echo "<td>";
				echo "<input type='text' name='text_update_tags[]' value='' size='15'>";
				echo "</td></tr>";
			}
			
			// add table in new row for ventil
/*
			echo "<tr id='tr_ventil_$tr_id'><td colspan=4 style='margin: 0px; border-bottom-width: 0px; padding: 0px;'>";
			echo "<form name='form_ventil' method='POST' action=''>";
			echo "<input type='hidden' name='hidden_ventil_tr_id' value='$tr_id'>";
			echo "<table id='table_ventil_$tr_id' style='visibility:hidden;display:none;font-size:small'>";
			echo "<tr><td><a href='javascript:void(0)' onClick=\"addRow('table_ventil_$tr_id', '$desc')\">+</a></td><td>Desc</td><td>Montant</td><td>Tags</td><td><input type ='submit' value='apply' name='submit_ventil'></tr>";
			echo "<tr><td>&nbsp;</td><td><input type='text' name='ventil_desc[]' value='$desc'></td><td><input type='text' name='ventil_amount[]'></td><td><input type='text' name='ventil_tags[]'></td><td>&nbsp;</td></tr>";
			echo "</table>";
			echo "</form>";
			echo "</td></tr>";
*/
		}
		echo "<tr><td>&nbsp;</td><td>
		<input type='submit' name='submit_reconciled' value='Update Reconciled'>
		</td><td>&nbsp;</td><td>
		<input type='submit' name='submit_update_tags' value='Update Tags'></td></tr>";
		echo "</table></form>";
		echo "</p>";
	}
}

class html_all_transaction_table extends html_transaction_table
{
	var $acc;
	var $date1;
	var $date2;
	
	function __construct($title, &$acc, $date1, $date2)
	{
		parent::__construct($title);
		$this->acc = $acc;
		$this->date1 = $date1;
		$this->date2 = $date2;
	}

	function get_transaction_row()
	{
		return $this->acc->get_transactions($this->date1, $this->date2, 0,0);
	}
}

class html_tag_transaction_table extends html_transaction_table
{
	var $acc;
	var $date1;
	var $date2;
	var $tag_name;
	
	function __construct($title, &$acc, $date1, $date2, $tag_name)
	{
		parent::__construct($title);
		$this->acc = $acc;
		$this->date1 = $date1;
		$this->date2 = $date2;
		$this->tag_name = $tag_name;
	}

	function get_transaction_row()
	{
		return $this->acc->get_tag_transactions($this->date1, $this->date2, $this->tag_name);
	}
}

class html_multiple_tag_transaction_table extends html_transaction_table
{
	var $acc;
	var $date1;
	var $date2;
	var $tag_array;
	var $type; // 1 : intersection, 2 : union
	
	function __construct($title, &$acc, $date1, $date2, &$tag_array, $type)
	{
		parent::__construct($title);
		$this->acc = $acc;
		$this->date1 = $date1;
		$this->date2 = $date2;
		$this->tag_array = $tag_array;
		$this->type = $type;
	}

	function get_transaction_row()
	{
		if ($this->type == 1)
			return $this->acc->get_intersect_tag_transactions($this->tag_array);
			/*
		elseif ($type == 2)
			return $this->acc->get_intersect_tag_transactions($this->tag_array);
			*/
	}
}

?>
