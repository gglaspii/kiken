<?php
function process_http_post(&$acc, &$p_array)
{
	//print_r($p_array);
	
	if (isset($p_array["add_transaction"]))
	{
		$tdate = $p_array["tdate"];
		$tdesc = $p_array["desc"];
		$tamount = $p_array["montant"];
		$tags = $p_array["tags"];
	
		$acc->add_transaction($tdate, $tdesc, $tamount, $tags);
	}
	elseif (isset($p_array["datafile"]))
	{
		process_parse_ofx($acc, $p_array["datafile"]);
	}
	elseif (isset($p_array["add_tag"]) && $p_array["add_tag"] === "Add")
	{
		$acc->add_tag($p_array["add_name"]);
	}
	elseif (isset($p_array["action"]) && $p_array["action"] === "update_tag")
	{
		$acc->update_tag($p_array["id"], $p_array["name"]);
	}
	elseif (isset($p_array["action"]) && $p_array["action"] === "remove_tag")
	{
		$acc->remove_tag($p_array["id"]);
	}
	elseif (isset($p_array["submit_update_tags"]))
	{
		process_update_tags($acc, $p_array);
	}
	elseif (isset($p_array["submit_reconciled"]))
	{
		echo "todo";
		$acc->reconciled($p_array["hidden_transaction_id"], $p_array["reconciled"]);
	}
	elseif (isset($p_array["update_transaction"]))
	{
		$acc->update_transaction($p_array["t_id"], $p_array["tdate"], NULL, $p_array["desc"], $p_array["montant"], $p_array["tags"]);
	}
	elseif (isset($p_array["add_transaction_schedule"]))
	{
		$acc->add_transaction_schedule($p_array["next_date"], $p_array["desc"], $p_array["amount"], $p_array["tags"], $p_array["type"]);
	}
	elseif (isset($p_array["update_transaction_schedule"]))
	{
		$acc->update_transaction_schedule($p_array["t_id"], $p_array["next_date"], $p_array["desc"], $p_array["amount"], $p_array["tags"], $p_array["type"]);
	}
	elseif (isset($p_array["delete_transaction_schedule"]))
	{
		$acc->delete_transaction_schedule($p_array["t_id"]);
	}
	elseif (isset($p_array["hidden_tr_id_to_delete"]) && $p_array["hidden_tr_id_to_delete"] > 0)
	{
		$acc->delete_transaction($p_array["hidden_tr_id_to_delete"]);
	}
	elseif (isset($p_array["settings_update_solde"]))
	{
		$new_solde = str_replace(",", ".", $p_array["settings_solde"]);
		$acc->solde = $new_solde;
		$acc->update_solde($new_solde);
		return 1;
	}
	elseif (isset($p_array["ventil_transaction"]))
	{
		$tdate = $p_array["tdate"];
		$tdesc = $p_array["desc"];
		$tamount = $p_array["montant"];
		$tags = $p_array["tags"];
		$t_id = $p_array["t_id"];

		$nbvalue = count($tdate);

		for($i=0;$i<$nbvalue;$i++)
		{	
			$acc->add_transaction($tdate[$i], $tdesc[$i], $tamount[$i], $tags[$i], $t_id);
		}
		// mark parent transaction as splitted so it will no more be used
		$acc->set_transaction_as_splitted($t_id, 1);
	}
    // tag_auto.php
    elseif (isset($p_array["add_auto_tag"]))
    {
        $regexp = $p_array["add_name"];
        $tag_id_1 = $p_array["tag_1"];
        $tag_id_2 = $p_array["tag_2"];
        $tag_id_3 = $p_array["tag_3"];
        $tr_type = $p_array["tr_type"];

        $acc->add_tag_auto($regexp, $tag_id_1, $tag_id_2, $tag_id_3, $tr_type);
    }
    elseif (isset($p_array["hidden_tag_auto_id_del"]))
    {
        if ($p_array["hidden_tag_auto_id_del"]>0)
            $acc->del_tag_auto($p_array["hidden_tag_auto_id_del"]);
    }
    elseif (isset($p_array["f_tag_auto_preview_submit"]) && isset($p_array["tr_auto_tag"]))
    {
        $tr_id_array = $p_array["tr_auto_tag"];
        $tag1 = $p_array["t1_name"];
        $tag2 = $p_array["t2_name"];
        $tag3 = $p_array["t3_name"];
        $tag_str_list = "";
        foreach($tr_id_array as $index => $tr_id) 
        {
            $tag_str_list = "$tag1,$tag2,$tag3";
            $acc->bind_transaction_tags($tr_id, $tag_str_list, true, true);
        }
    }
}

function process_update_tags(&$acc, &$p_array)
{
	$tags_ar = $p_array["text_update_tags"];
	$trans_id_arr = $p_array["hidden_transaction_id"];
	
	foreach ($tags_ar as $key => $val)
	{
		if ($val)
		{
			echo "<br>key = $key, val = $val, trans_id = $trans_id_arr[$key]";
			$acc->update_transaction($trans_id_arr[$key], "", "", "", "", $val);
		}
	}
}

function process_parse_ofx(&$acc, $datafile)
{
	$tdate;
	$tdesc = "";
	$amount = 0;
	$tags = "";
	$tdatetime = "";
	
	$file_path = "ressource/".$datafile;
	$file_handle = fopen($file_path, "r");
	while ($file_handle && !feof($file_handle)) {
	   $line = fgets($file_handle);
		$res = strstr($line, "DTPOSTED");
		if (strstr($line, "</STMTTRN>"))
		{
			echo "end, amount = $amount <br>";
			if ($amount !=0)
			{
				$tdesc = preg_replace('/\s+/', ' ',$tdesc);
				echo "end, tdesc = $tdesc, tdatetime=$tdatetime<br>";
				$acc->add_transaction($tdate, $tdesc, $amount, $tags, 0, $tdatetime);
				$amount = 0;
				$tdesc = "";
				$tdatetime = "";
			}
			else
				continue;
		}
		else if (strstr($line, "DTPOSTED"))
		{
			$tdate = str_replace("<DTPOSTED>", "", $line);
			$tdate = substr($tdate, 0, 4)."-".substr($tdate, 4, 2)."-".substr($tdate, 6, 2);
			echo "tdate = $tdate <br>";
		}
		else if (strstr($line, "TRNAMT"))
		{
			$amount = str_replace("<TRNAMT>", "", $line);
			echo "amount = $amount <br>";
		}
		else if (strstr($line, "FITID"))
		{
			$tdatetime = str_replace("<FITID>", "", $line);			
		}
		else if (strstr($line, "NAME"))
		{
			$tdesc .= str_replace("<NAME>", "", $line);
			
		}
		else if (strstr($line, "MEMO"))
		{
			$tdesc .= str_replace("<MEMO>", "", $line);
			echo "tdesc = $tdesc <br>";
		}
	}
	fclose($file_handle);
}

function process_http_get(&$acc, &$g_array)
{
	print_r($g_array);
}
?>
