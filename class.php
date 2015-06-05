<?php
require_once("connect.inc.php");
require("camembert/camembert.php");
require("pChart/pData.class");
require("pChart/pChart.class");

class KAccount {
    var $name;
    var $id;
    var $tags;
	var $solde;
	var $pref_first_month_day;
    
    var $res = null;
    
	function __construct($account_id)
	{
		$query = "select * from account where id = $account_id";
		$res=do_mysql_query($query);
		if (!mysql_num_rows($res))
			exit("account not found");
			
		$row = mysql_fetch_array($res,MYSQL_ASSOC);		
		$this->id = $row["id"];
		$this->name = $row["name"];
		$this->solde = $row["solde"];
		
		$this->pref_first_month_day = 1;
		if (0 && $this->name === "gg-ca")
			$this->pref_first_month_day = 4;
	}
  
  function get_id()
  {
    return $this->id;
  }

	function update_solde($solde)
	{
		$query = "UPDATE account SET solde = $solde WHERE user_id = $this->id;";
		do_mysql_query($query);
	}
	
	function check_schedule()
	{
		// check transaction that are <= now, and add them !
		$query = "SELECT * from transaction_schedule WHERE next_exec_date <= now() and account_id = $this->id;";
		$res=do_mysql_query($query );
		while ($row=mysql_fetch_array($res,MYSQL_ASSOC))
		{
			$this->add_transaction($row["next_exec_date"], $row["description"], $row["amount"], $row["tags"]);
			$id = $row["id"];
			// update for next time
			$inc_month = $row["inc_month"];
			$query = "UPDATE transaction_schedule SET next_exec_date = DATE_ADD(next_exec_date,INTERVAL $inc_month MONTH) WHERE ID = $id;";
			do_mysql_query($query);
		}
	}
	
	function add_transaction_schedule($next_date, $desc, $amount, $tags, $inc_month)
	{
		if ($amount[0] == '-') $type = "Expense"; else $type = "Income";
		$amount = str_replace(",", ".", $amount);
		
		$query = "INSERT INTO transaction_schedule(account_id, next_exec_date, description, amount, type, inc_day, inc_month, tags) VALUES($this->id, '$next_date', '$desc', $amount, '$type', 0, $inc_month, '$tags')";
		do_mysql_query($query);
	}

	function update_transaction_schedule($t_id, $next_date, $desc, $amount, $tags, $inc_month)
	{
		if ($amount[0] == '-') $type = "Expense"; else $type = "Income";
		$amount = str_replace(",", ".", $amount);
		
		$query = "UPDATE transaction_schedule SET next_exec_date='$next_date',description='$desc',amount=$amount,type='$type',tags='$tags', inc_month=$inc_month WHERE id = $t_id";
		do_mysql_query($query);
	}

	function delete_transaction_schedule($t_id)
	{
		$query = "DELETE FROM transaction_schedule WHERE id = $t_id";
		do_mysql_query($query);
	}
	
	function get_transaction_schedule()
	{
    	if ($this->res == null)
    	{
	    	$query = "SELECT * FROM transaction_schedule WHERE account_id = ".$this->id." order by next_exec_date DESC";
			$this->res=do_mysql_query($query );
    	}
    	$row = mysql_fetch_array($this->res,MYSQL_ASSOC);
    	if (!$row)
    	{
    		mysql_free_result($this->res);
			$this->res = null;
    	}
    	return $row;
	}
	
	function reconciled(&$all, &$reconciled)
	{
		do_mysql_query("START TRANSACTION");
		
		$query = "UPDATE transaction set status = 'Cleared' WHERE id IN (".implode(",", $all).")";
		$res = do_mysql_query($query);
		
		$query = "UPDATE transaction set status = 'Reconciled' WHERE id IN (".implode(",", $reconciled).")";
		$res = do_mysql_query($query);
		
		do_mysql_query("COMMIT");
	}
	
	function bind_transaction_tags($t_id, $tag_str_list, $bool_clean_tags, $in_transaction)
	{
		if ($in_transaction)
		{
			$query = "START TRANSACTION ;";
			$res=do_mysql_query($query);
		}
		
		if ($bool_clean_tags)
		{
			$query = "DELETE FROM transaction_tag WHERE transaction_id = $t_id ;";
			$res=do_mysql_query($query);
		}
		
		$arr_tags = explode(",", $tag_str_list);
		foreach ($arr_tags as $a_tag)
		{
			if ($a_tag && $a_tag!="")
			{
				$query = "INSERT INTO tag(name, account_id) values(TRIM('$a_tag'), $this->id) ;";
				$res=do_mysql_query($query, 1);
			}
			
			if ($t_id)
			{
				$query = "INSERT INTO transaction_tag(transaction_id, tag_id) select $t_id, id FROM tag where name=TRIM('$a_tag') and account_id = $this->id ;";
				$res=do_mysql_query($query);
			}
		}
		
		if ($in_transaction)
		{
			$query = "COMMIT ;";
			$res=do_mysql_query($query);
		}
	}
    
    function add_transaction($tdate, $tdesc, $amount, &$tags, $split_parent_id=0, $tdatetime='')
    {
    	if ($amount[0] == '-') $type = "Expense"; else $type = "Income";
		$amount = str_replace(",", ".", $amount);

		$sqltdatetime = "NULL";
		if (strlen($tdatetime)>0)
			$sqltdatetime = "'".$tdatetime."'";
    	
		$tdesc = mysql_real_escape_string($tdesc);
    	$query = "INSERT INTO transaction(tdate, description, amount, type, account_id, split_parent_id, tdatetime) 
    	VALUES ('$tdate', TRIM('$tdesc'), $amount, '$type', $this->id, $split_parent_id, $sqltdatetime);";

		$res=do_mysql_query($query);
		if (!$res)
			return;
			
		$transaction_id = mysql_insert_id();
		
		$this->bind_transaction_tags($transaction_id, $tags, 0, 1);
	}
	
	function delete_transaction($t_id)
	{
		// todo : check if t_id has a splitted_parent, if so, check if parent has other split children, if not, also delete splitted_parent
		$query = "DELETE FROM transaction WHERE id = $t_id";
		$res=do_mysql_query($query);
		$query = "DELETE FROM transaction_tag WHERE transaction_id = $t_id";
		$res=do_mysql_query($query);
	}

	function set_transaction_as_splitted($t_id)
	{
		$query = "UPDATE transaction set splitted = 1 where id = $t_id";
		$res=do_mysql_query($query);
	}
	
	function update_transaction($t_id, $t_date, $t_datetime, $t_desc, $amount, $t_tags)
	{
		if ($amount && $amount[0] == '-') $type = "Expense"; else $type = "Income";
		$amount = str_replace(",", ".", $amount);
		
		$query = "START TRANSACTION ;";
		$res=do_mysql_query($query);
		
		if ($t_date)
		{
			$query = "UPDATE transaction SET tdate = '$t_date' WHERE id = $t_id ;";
			$res=do_mysql_query($query);
		}
		if ($t_datetime)
		{
			$query = "UPDATE transaction SET tdatetime = '$t_datetime' WHERE id = $t_id ;";
			$res=do_mysql_query($query);
		}
		if ($t_desc)
		{
			$t_desc = mysql_real_escape_string($t_desc);
			$query = "UPDATE transaction SET description = '$t_desc' WHERE id = $t_id ;";
			$res=do_mysql_query($query);
		}
		if ($amount)
		{
			$query = "UPDATE transaction SET amount = $amount WHERE id = $t_id ;";
			$res=do_mysql_query($query);
			$query = "UPDATE transaction SET type = '$type' WHERE id = $t_id ;";			
			$res=do_mysql_query($query);
		}

		$this->bind_transaction_tags($t_id, $t_tags, 1, 0);
	
		$query = "COMMIT ;";
		$res=do_mysql_query($query);
	}

    function get_transactions_this_month($offset, $limit)
    {
    	$date2 = date("Y-m-d");
    	$date1 = date("Y-m-01") ;
    	
    	return $this->get_transactions($date1, $date2, 0, 0);
    }
    
    function get_transactions($date1, $date2, $offset, $limit) {
    	if ($this->res == null)
    	{
	    	$query = "SELECT transaction.id as tr_id, transaction.tdate, transaction.description, transaction.amount, transaction.type, transaction.status, GROUP_CONCAT(tag.name SEPARATOR ', ') as tags, transaction.split_parent_id, transaction.splitted, t2.description as split_desc
	    	FROM ( (account LEFT JOIN transaction ON account.id = transaction.account_id)  
	    	LEFT JOIN transaction_tag ON transaction.id = transaction_tag.transaction_id) 
	    	LEFT JOIN tag ON transaction_tag.tag_id = tag.id 
	    	LEFT JOIN transaction t2 on transaction.split_parent_id = t2.id
	    	WHERE account.id = $this->id
	    	AND transaction.tdate>='$date1' AND transaction.tdate<='$date2' GROUP BY tr_id ORDER BY transaction.tdate DESC";
			$this->res=do_mysql_query($query );
    	}
    	$row = mysql_fetch_array($this->res,MYSQL_ASSOC);
    	if (!$row)
    	{
    		mysql_free_result($this->res);
			$this->res = null;
    	}
    	return $row;
    }
    
    function get_tags()
	{
    	if ($this->res == null)
    	{
	    	$query = "SELECT * FROM tag WHERE account_id = $this->id order by name";
			$this->res=do_mysql_query($query );
    	}
    	$row = mysql_fetch_array($this->res,MYSQL_ASSOC);
    	if (!$row)
    	{
    		mysql_free_result($this->res);
			$this->res = null;
    	}
    	return $row;
    }

    function get_auto_tags()
    {
        if ($this->res == null)
    	{
	    	$query = "SELECT tag_auto.id,`regexp`, 
            t1.id as t1_id, t1.name as t1_name,
            t2.id as t2_id, t2.name as t2_name,
            t3.id as t3_id, t3.name as t3_name,
            t4.id as t4_id, t4.name as t4_name,
            t5.id as t5_id, t5.name as t5_name,tr_type  
            FROM tag_auto left join tag t1 on tag_auto.tag_id_1 = t1.id 
            left join tag t2 on tag_auto.tag_id_2 = t2.id 
            left join tag t3 on tag_auto.tag_id_3 = t3.id 
            left join tag t4 on tag_auto.tag_id_4 = t4.id 
            left join tag t5 on tag_auto.tag_id_5 = t5.id 
            WHERE tag_auto.account_id = $this->id order by `regexp`";
			$this->res=do_mysql_query($query );
    	}
    	$row = mysql_fetch_array($this->res,MYSQL_ASSOC);
    	if (!$row)
    	{
    		mysql_free_result($this->res);
			$this->res = null;
    	}
    	return $row;
    }

    function get_auto_tag($id)
    {
        $query = "SELECT tag_auto.id,`regexp`, 
        t1.id as t1_id, t1.name as t1_name,
        t2.id as t2_id, t2.name as t2_name,
        t3.id as t3_id, t3.name as t3_name,
        t4.id as t4_id, t4.name as t4_name,
        t5.id as t5_id, t5.name as t5_name, tr_type  
        FROM tag_auto left join tag t1 on tag_auto.tag_id_1 = t1.id 
        left join tag t2 on tag_auto.tag_id_2 = t2.id 
        left join tag t3 on tag_auto.tag_id_3 = t3.id 
        left join tag t4 on tag_auto.tag_id_4 = t4.id 
        left join tag t5 on tag_auto.tag_id_5 = t5.id 
        WHERE tag_auto.account_id = $this->id and tag_auto.id = $id";
		$this->res=do_mysql_query($query );

    	$row = mysql_fetch_array($this->res,MYSQL_ASSOC);

    	mysql_free_result($this->res);
		$this->res = null;

    	return $row;
    }

    function auto_tag_last_transactions()
    {
        $row_res = array();
        $auto_tag_array = array();

        // try regexp on this transaction foreach auto tag,
        while($row = $this->get_auto_tags())
        {
            $id = $row["id"];
	        $regexp = $row["regexp"];
	        $t1_id = $row["t1_id"];
            $t1_name = $row["t1_name"];
            $t2_id = $row["t2_id"];
            $t2_name = $row["t2_name"];
            $t3_id = $row["t3_id"];
            $t3_name = $row["t3_name"];
            $t4_id = $row["t4_id"];
            $t4_name = $row["t4_name"];
            $t5_id = $row["t5_id"];
            $t5_name = $row["t5_name"];
            $tr_type = $row["tr_type"];
            $pattern = "/$regexp/i";

            $at_entry = array("id"=>$id, "regexp"=>$regexp, "t1_id"=>$t1_id, "t1_name"=>$t1_name,
                        "t2_id"=>$t2_id, "t2_name"=>$t2_name,
                        "t3_id"=>$t3_id, "t3_name"=>$t3_name,
                        "t4_id"=>$t4_id, "t4_name"=>$t4_name,
                        "t5_id"=>$t5_id, "t5_name"=>$t5_name, "tr_type"=>$tr_type, "pattern"=>$pattern);

            array_push($auto_tag_array, $at_entry);
        }

        while($row = $this->get_transactions(strtotime(date('Y-m-d') . ' -1 month'), date('Y-m-d'), 0, 0))
        {
	        $tr_id = $row["tr_id"];
	        $tags = $row["tags"];
	        $tdate = $row["tdate"];
	        $desc = $row["description"];
            $amount = $row["amount"];

            foreach($auto_tag_array as $at_item)
            {
                if (preg_match($at_item["pattern"], $desc) === 1 && strlen($tags)==0)
                {
                    $entry = array("desc"=>$desc,"regexp"=>$at_item["pattern"]);
                    array_push($row_res, $entry);
                    $tag_str_list = $at_item["t1_name"].",".$at_item["t2_name"].",".$at_item["t3_name"];
                    $this->bind_transaction_tags($tr_id, $tag_str_list, true, true);
                }
            }
        }
        return $row_res;
    }

    function add_tag_auto($regexp, $id1, $id2, $id3, $tr_type='Expense')
    {
        $query = "INSERT INTO tag_auto(account_id, `regexp`, tag_id_1, tag_id_2, tag_id_3, tr_type) 
        VALUES($this->id, '$regexp', $id1, $id2, $id3, '$tr_type')";
        $res = do_mysql_query($query);
    }

    function del_tag_auto($id)
    {
        $query = "DELETE FROM tag_auto WHERE id = $id";
        $res = do_mysql_query($query);
    }
	
	function add_tag($name)
	{
		$query = "INSERT INTO tag(name, account_id) VALUES(TRIM('$name'), $this->id)";
		$res = do_mysql_query($query);
	}
	
	function update_tag($id, $name)
	{
		$query = "UPDATE tag set name='$name' WHERE id=$id";
		$res = do_mysql_query($query);
	}
	
	function remove_tag($id)
	{
		$query = "START TRANSACTION";
		$res = do_mysql_query($query);
		
		$query = "DELETE from tag WHERE id=$id";
		$res = do_mysql_query($query);
		
		$query = "DELETE from transaction_tag WHERE tag_id=$id";
		$res = do_mysql_query($query);
		
		$query = "COMMIT";
		$res = do_mysql_query($query);
	}
    
    function get_tag_transactions($date1, $date2, $tag_name)
    {		
    	if ($this->res == null)
    	{
			$query = "SELECT transaction.id as tr_id, tdate, description, amount, type, status, GROUP_CONCAT(tag.name SEPARATOR ', ') as tags 
			FROM transaction, transaction_tag, tag 
			WHERE transaction.id = transaction_tag.transaction_id AND transaction_tag.tag_id = tag.id 
			AND transaction.id IN 
			(SELECT transaction.id 
			FROM transaction, transaction_tag, tag 
			WHERE transaction.id = transaction_tag.transaction_id AND transaction_tag.tag_id = tag.id 
			AND tag.name = '$tag_name' 
			AND transaction.splitted = 0 
			AND transaction.account_id = $this->id order by tdate desc) GROUP BY transaction.id ORDER BY tdate DESC";
			
			$this->res=do_mysql_query($query );
    	}
    	$row = mysql_fetch_array($this->res,MYSQL_ASSOC);
    	if (!$row)
    	{
    		mysql_free_result($this->res);
			$this->res = null;
    	}
    	return $row;
    }
	
	function get_intersect_tag_transactions(&$tag_array)
    {				
    	if ($this->res == null)
    	{
			$suf ="(0 ";
			foreach ($tag_array as $val)
			{
				$suf .= "OR tag.name = '$val' ";
			}
			$suf .= ")";
			
			$array_nb_item = count($tag_array);
		
			$query = "SELECT transaction.id as tr_id, tdate, description, amount, type, status, GROUP_CONCAT(tag.name SEPARATOR ', ') as tags
			FROM transaction, transaction_tag, tag 
			WHERE transaction.id = transaction_tag.transaction_id AND transaction_tag.tag_id = tag.id 
			AND transaction.id IN 
			(SELECT t.tid FROM (
				select transaction.id as tid, count(*) as cpt 
				FROM transaction, transaction_tag, tag WHERE transaction_tag.transaction_id = transaction.id AND transaction_tag.tag_id = tag.id  
				AND transaction.account_id = $this->id 
				AND transaction.splitted = 0
				AND $suf GROUP BY transaction.id having cpt = $array_nb_item
				) as t) GROUP BY transaction.id";
			
			$this->res=do_mysql_query($query );
    	}
    	$row = mysql_fetch_array($this->res,MYSQL_ASSOC);
    	if (!$row)
    	{
    		mysql_free_result($this->res);
			$this->res = null;
    	}
    	return $row;
    }
    
	function get_expensive_tags($date1, $date2, $arr)
	{
		$camembert = new camembert(); # initialisation
		
		// fill temp table / data
		$query = "DELETE FROM best_tags;";
		$res=do_mysql_query($query);
		if(!$res)
			print mysql_error();
			
		$query = "INSERT INTO best_tags(transaction_id, tag_id, tag_name, amount) 
		SELECT transaction_id, tag_id, tag.name, amount 
		FROM transaction, transaction_tag, tag 
		WHERE transaction.id = transaction_tag.transaction_id and transaction_tag.tag_id = tag.id 
		AND transaction.tdate >= '$date1' AND transaction.tdate <= '$date2' 
		AND transaction.type='Expense' 
		AND transaction.account_id = $this->id
		AND transaction.splitted = 0
		order by amount desc";
		$res=do_mysql_query($query );
		if(!$res)
			error(mysql_error());
		
		for ($i=0;$i<5;$i++)
		{
			$higher_tag_id = 0;
			$higher_tag_name = "";
			$higer_tag_amount = 0;
			// get higher tag from temp data
			$query = "SELECT tag_id, tag_name, -ROUND(sum(amount)) as tot FROM best_tags group by tag_id order by tot desc LIMIT 0,1";
			$res=do_mysql_query($query );
			while ($row=mysql_fetch_array($res,MYSQL_ASSOC))
			{
				$higher_tag_id = $row["tag_id"];
				$higher_tag_name = $row["tag_name"];
				$higer_tag_amount = $row["tot"];
				
				// save this in array
				//echo "id = $higher_tag_id, name = $higher_tag_name, amount = $higer_tag_amount <br>";
				$camembert->add_tab( $higer_tag_amount, $higher_tag_name);
			}
			
			// remove all transaction that contains higher tag_id		
			
			// same date !!!!!!!!!
			$query = "DELETE FROM best_tags WHERE transaction_id IN (SELECT transaction_id FROM transaction_tag WHERE tag_id = $higher_tag_id)";
			$res=do_mysql_query($query );
			if(!$res)
				error(mysql_error());
			// process next
		}
		
		/*
		$query="SELECT name, ROUND(sum(amount)) as tot FROM transaction, transaction_tag, tag WHERE transaction.id = transaction_tag.transaction_id and transaction_tag.tag_id = tag.id AND transaction.type='Expense' group by transaction_tag.tag_id order by tot desc LIMIT 0,8";
		
		$res=do_mysql_query($query );
		while ($row=mysql_fetch_array($res,MYSQL_ASSOC))
		{
			$tot = $row["tot"];
			$name = $row["name"];
			//echo "$tot, $name<br>";
			$camembert->add_tab( $tot, $name);
		}
		*/
	
	
		 //$camembert->trier_tab(); # Facultatif, les donnees sont triees dans l'ordre decroissant
		 // $camembert->affiche_tab(); # Debug
		 
		 // on genere l'image au format PNG
		 $camembert->stat2png(3, 30, "ressource/stat_exp_tags.png"); # 1er argument (2 ou 3 pour la 2D ou la 3D) - 2eme argument hauteur en pixel de l'effet 3D (mettre quelque chose meme pour la 2D)
		 echo "<img src='ressource/stat_exp_tags.png'>";
	}

	function get_expensive_tags_ext_highchart($date1, $date2)
	{
		$query = "SELECT transaction_id, tag_id, tag.name as tname, SUM(amount) as somme, GROUP_CONCAT(transaction.description,' : ', amount SEPARATOR '<br>') as tr_desc
		FROM transaction, transaction_tag, tag 
		WHERE transaction.id = transaction_tag.transaction_id and transaction_tag.tag_id = tag.id 
		AND transaction.tdate >= '$date1' AND transaction.tdate <= '$date2' 
		AND transaction.account_id = $this->id 
		AND transaction.splitted = 0
		group by tag_id having somme < 0
		order by somme asc ;";
		
		$res=do_mysql_query($query );
		if(!$res)
			error(mysql_error());
			
		$rows = array();

		$output = array();
		$output['data'] = array();
		$output['type'] = 'pie';
		$output['name'] = 'Les plus';

		$json_output = array();
		
		$res=do_mysql_query($query );
		while ($row=mysql_fetch_array($res,MYSQL_ASSOC))
		{
		  $rows["name"] = $row["tname"];
		  $rows["info"] = $row["tr_desc"];
		  $rows["y"] = -round($row["somme"]);
		  $rows["tag_histo"] = $row["tname"];
		  array_push($output['data'],$rows);
		}
		
		//$json_output[0] = $output;

		$title = "Repartition des depenses";
		$subtitle = "";
		$this->highchart_build_generic_pie(&$json_output, $title, $subtitle, $categories, $output, "{point.info}");

		print json_encode($json_output, JSON_NUMERIC_CHECK);
	
	}
	
	function get_expensive_tags_ext($date1, $date2)
	{
	 
	 // fill temp table / data
	$query = "DELETE FROM best_tags;";
	$res=do_mysql_query($query);
	if(!$res)
		error(mysql_error());
		
	$query = "INSERT INTO best_tags(transaction_id, tag_id, tag_name, amount) 
	SELECT transaction_id, tag_id, tag.name, amount 
	FROM transaction, transaction_tag, tag 
	WHERE transaction.id = transaction_tag.transaction_id and transaction_tag.tag_id = tag.id 
	AND transaction.tdate >= '$date1' AND transaction.tdate <= '$date2' 
	AND transaction.type='Expense' 
	AND transaction.account_id = $this->id
	AND transaction.splitted = 0
	order by amount desc";
	$res=do_mysql_query($query );
	if(!$res)
		error(mysql_error());
		
	$higher_tag_values = array();
	$higher_tag_names = array();
	$legend_map = array();

	for ($i=0;$i<5;$i++)
	{
		$higher_tag_id = 0;
		$higher_tag_name = "";
		$higer_tag_amount = 0;
		// get higher tag from temp data
		$query = "SELECT tag_id, tag_name, -ROUND(sum(amount)) as tot FROM best_tags group by tag_id order by tot desc LIMIT 0,1";
		$res=do_mysql_query($query );
		if ($i == 0 && mysql_num_rows($res) == 0) return;
		while ($row=mysql_fetch_array($res,MYSQL_ASSOC))
		{
			$higher_tag_id = $row["tag_id"];
			$higher_tag_name = $row["tag_name"];
			$higer_tag_amount = $row["tot"];
			
			// save this in array
			//echo "id = $higher_tag_id, name = $higher_tag_name, amount = $higer_tag_amount <br>";
			//$camembert->add_tab( $higer_tag_amount, $higher_tag_name);
			 $higher_tag_values[$i] = $higer_tag_amount;
			  $higher_tag_names[$i] = $higher_tag_name;
		}
		
		// remove all transaction that contains higher tag_id		
		
		// same date !!!!!!!!!
		$query = "DELETE FROM best_tags WHERE transaction_id IN (SELECT transaction_id FROM transaction_tag WHERE tag_id = $higher_tag_id)";
		$res=do_mysql_query($query );
		if(!$res)
			error(mysql_error());
		// process next
	}
	
	 // Dataset definition 
	 $DataSet = new pData;
	 $DataSet->AddPoint($higher_tag_values,"Serie1");
	 $DataSet->AddPoint( $higher_tag_names,"Serie2");
	 $DataSet->AddAllSeries();
	 $DataSet->SetAbsciseLabelSerie("Serie2");

	 // Initialise the graph
	 $Test = new pChart(500,250);
	 $Test->drawFilledRoundedRectangle(7,7,473,243,5,240,240,240);
	 $Test->drawRoundedRectangle(5,5,475,245,5,230,230,230);
	 $Test->createColorGradientPalette(195,204,56,223,110,41,5);

	 // Draw the pie chart
	 $Test->setFontProperties("font/tahoma.ttf",8);
	 $Test->AntialiasQuality = 0;
	 $Test->drawPieGraph($DataSet->GetData(),$DataSet->GetDataDescription(),180,130,110,PIE_VALUE_PERCENTAGE_LABEL,TRUE,50,20,5);
	 $Test->drawPieLegend(350,15,$DataSet->GetData(),$DataSet->GetDataDescription(),250,250,250, $legend_map);

	 // Write the title
	 $Test->setFontProperties("font/MankSans.ttf",10);
	 $strdate1 = date("d/m/y", strtotime($date1));
	 $strdate2 = date("d/m/y", strtotime($date2));
	 $Test->drawTitle(10,20,"Repartition des dépenses du $strdate1 au $strdate2",100,100,100);

	 $Test->Render("ressource/stat_exp_tags.png");
	 echo "<img src='ressource/stat_exp_tags.png', usemap='#legend' border='0'>";
	 echo "<map name='legend'>";
	foreach($legend_map as $key => $value)
	{
		echo "<area shape='rect' coords='$value[0],$value[1],$value[2],$value[3]' href='?tag_name=$key'>";
	}
	echo "</map>";

	}
	
	function prv_generate_line_chart(&$result, $tag_name, $month_first_date, $on_last_month_number)
	{
		$stat_res = array();
		
		$DataSet = new pData;   
		
		$avg_amount = 0;
		$tot_amount = 0;
			
		/* fetch rows in reverse order */
		$index = 0;
		for ($i = mysql_num_rows($result) - 1; $i >= 0; $i--) {
			if (!mysql_data_seek($result, $i)) {
				echo "Cannot seek to row $i: " . mysql_error() . "\n";
				continue;
			}

			if (!($row = mysql_fetch_assoc($result))) {
				continue;
			}
			
			$mname = $row['mname'];
			$ydate = $row['ydate'];
			$tot = $row['tot'];
			$stat_res[$index]["Name"] = $mname."-".$ydate;
			$stat_res[$index]["Serie1"] = $tot;
			
			$DataSet->AddPoint(round(-$tot, 2), $tag_name, $mname."-".$ydate);
			$tot_amount += -$tot;
			
			$index++;
		}
		
		if ($index > 0)
		{
			$avg_amount = round($tot_amount /  mysql_num_rows($result), 2);
			$tot_amount = round($tot_amount, 2);
		}

		mysql_free_result($result);
		
		// Dataset definition		
		$DataSet->AddAllSeries();
		$DataSet->SetYAxisName("");
		$DataSet->SetYAxisUnit("euro");
		$DataSet->SetXAxisName("Debut de mois le $month_first_date");
		  
		 // Initialise the graph   
		 $Test = new pChart(700,230);
		 $Test->setFontProperties("font/tahoma.ttf",8);   
		 $Test->setGraphArea(70,30,680,200);   
		 $Test->drawFilledRoundedRectangle(7,7,693,223,5,240,240,240);   
		 $Test->drawRoundedRectangle(5,5,695,225,5,230,230,230);   
		 $Test->drawGraphArea(255,255,255,TRUE);
		 $Test->drawScale($DataSet->GetData(),$DataSet->GetDataDescription(),SCALE_NORMAL,150,150,150,TRUE,0,2);   
		 $Test->drawGrid(4,TRUE,230,230,230,50);
		  
		 // Draw the 0 line   
		 $Test->setFontProperties("font/tahoma.ttf",6);   
		 $Test->drawTreshold(0,143,55,72,TRUE,TRUE);   
		  
		 // Draw the line graph
		 $Test->drawLineGraph($DataSet->GetData(),$DataSet->GetDataDescription());   
		 $Test->drawPlotGraph($DataSet->GetData(),$DataSet->GetDataDescription(),3,2,255,255,255);
		 // Draw values
		 $Test->setFontProperties("font/tahoma.ttf",8);     
		 $Test->writeValues($DataSet->GetData(),$DataSet->GetDataDescription(),"$tag_name");  
		  
		 // Finish the graph   
		 $Test->setFontProperties("font/tahoma.ttf",8);   
		 //$Test->drawLegend(75,35,$DataSet->GetDataDescription(),255,255,255);   
		 $Test->setFontProperties("font/tahoma.ttf",10);   
		 $Test->drawTitle(60,22,"$tag_name sur les $on_last_month_number derniers mois, tot = $tot_amount, avg = $avg_amount",50,50,50,585);   
		 $Test->Render("ressource/stat_line_tag.png");
		 
		 echo "<img src='ressource/stat_line_tag.png', border='0'>";
		
		return 0;
	}
	
	function get_multiple_tag_intersect_line_chart($tag_array, $month_first_date, $on_last_month_number)
	{
		$suf ="(0 ";
		foreach ($tag_array as $val)
		{
			$suf .= "OR tag.name = '$val' ";
		}
		$suf .= ")";
		
		$array_nb_item = count($tag_array);
		
		$query = "select SUM(amount) as tot, MONTH(DATE_SUB(tdate,INTERVAL $month_first_date DAY)) AS ddate, 
		MONTH(DATE_SUB(tdate,INTERVAL $month_first_date DAY)) as mname, 
		YEAR(DATE_SUB(tdate,INTERVAL $month_first_date DAY)) as ydate  
		FROM transaction
		WHERE id IN (
		SELECT t.tid FROM (select transaction.id as tid, count(*) as cpt 
		FROM transaction, transaction_tag, tag WHERE transaction_tag.transaction_id = transaction.id AND transaction_tag.tag_id = tag.id  
		AND transaction.account_id = $this->id AND transaction.splitted = 0 AND $suf GROUP BY transaction.id having cpt = $array_nb_item) as t
		)
		GROUP BY CONCAT(ddate, ydate) ORDER BY ydate DESC, ddate DESC LIMIT $on_last_month_number";
		
		$result=do_mysql_query($query);
		if(!$result)
		{
			print mysql_error();
			return;
		}
		
		$tagconcatname = implode("-", $tag_array);

		$this->prv_generate_line_chart($result, $tagconcatname, $month_first_date, $on_last_month_number);
		mysql_free_result($result);
	}
	
	function get_multiple_tag_union_line_chart($tag_array, $month_first_date, $on_last_month_number)
	{
		$suf ="(0 ";
		foreach ($tag_array as $val)
		{
			$suf .= "OR tag.name = '$val' ";
		}
		$suf .= ")";
		
		$array_nb_item = count($tag_array);
		
		$query = "select SUM(amount) as tot, MONTH(DATE_SUB(tdate,INTERVAL $month_first_date DAY)) AS ddate, 
		MONTH(DATE_SUB(tdate,INTERVAL $month_first_date DAY)) as mname, 
		YEAR(DATE_SUB(tdate,INTERVAL $month_first_date DAY)) as ydate  
				FROM transaction
				WHERE id IN (
				SELECT transaction.id FROM transaction, transaction_tag, tag WHERE transaction_tag.transaction_id = transaction.id AND transaction_tag.tag_id = tag.id 
				AND transaction.account_id = $this->id AND transaction.splitted = 0 AND $suf)
				GROUP BY ddate ORDER BY ddate DESC LIMIT $on_last_month_number";
		
		$result=do_mysql_query($query);
		if(!$result)
		{
			print mysql_error();
			return;
		}
		
		$tagconcatname = implode("-", $tag_array);

		$this->prv_generate_line_chart($result, $tagconcatname, $month_first_date, $on_last_month_number);
		mysql_free_result($result);
	}
	
	function get_tag_line_chart($tag_name, $month_first_date, $on_last_month_number)
	{
		$query = "select SUM(amount) as tot, CONCAT(YEAR(DATE_SUB(tdate,INTERVAL $month_first_date DAY)), LPAD(MONTH(DATE_SUB(tdate,INTERVAL $month_first_date DAY)),2,'0')) AS ddate, 
		MONTH(DATE_SUB(tdate,INTERVAL $month_first_date DAY)) as mname,
		YEAR(DATE_SUB(tdate,INTERVAL $month_first_date DAY)) as ydate  
		FROM transaction, transaction_tag, tag WHERE transaction_tag.transaction_id = transaction.id AND transaction_tag.tag_id = tag.id 
		AND transaction.account_id = $this->id AND transaction.splitted = 0 AND tag.name = '$tag_name' GROUP BY ddate ORDER BY ddate DESC LIMIT $on_last_month_number";
		$result=do_mysql_query($query);
		if(!$result)
		{
			print mysql_error();
			return;
		}
		$this->prv_generate_line_chart($result, $tag_name, $month_first_date, $on_last_month_number);
	}

	function highchart_build_generic_line($chart_array_info, $i_title, $i_sub_title, $categories, $series, $i_point_format)
	{
	  $chart = array();
	  $chart["type"] = "line";

	  $title = array();
	  $title["text"] = $i_title;

	  $sub_title = array();
	  $sub_title["text"] = $i_sub_title;

	  $tooltip = array();
	  $tooltip["pointFormat"] = $i_point_format;

	  $chart_array_info["chart"] = $chart;
	  $chart_array_info["title"] = $title;
	  $chart_array_info["subtitle"] = $sub_title;
	  $chart_array_info["xAxis"] = $categories;
	  $chart_array_info["series"][0] = $series;	  
	  $chart_array_info["tooltip"] = $tooltip;
	}

	function highchart_build_generic_pie($chart_array_info, $i_title, $i_sub_title, $categories, $series, $i_point_format)
	{
	  $chart = array();
	  $chart["type"] = "pie";

	  $title = array();
	  $title["text"] = $i_title;

	  $sub_title = array();
	  $sub_title["text"] = $i_sub_title;

	  $tooltip = array();
	  $tooltip["pointFormat"] = $i_point_format;

	  $chart_array_info["chart"] = $chart;
	  $chart_array_info["title"] = $title;
	  $chart_array_info["subtitle"] = $sub_title;
	  $chart_array_info["xAxis"] = $categories;
	  $chart_array_info["series"][0] = $series;
	  $chart_array_info["tooltip"] = $tooltip;
	}

	function get_account_balance_chart_highchart($date1, $date2)
	{
		$sum_expense = 0-round($this->get_sum_between("Expense", ($date1), ($date2)), 2);
        $sum_income = round($this->get_sum_between("Income", ($date1), ($date2)), 2);

        $chart = json_decode("{\"type\":\"bar\",\"height\": 200, \"renderTo\":\"container_balance\"}", true);
			
		$rows = array();
		//$categories=array("categories"=>"Balance");
		$output = array();
		//$output['data'] = array($sum_expense, $sum_income);
		//$output['type'] = 'line';
		//$output['name'] = array("depense", "revenu");

		$json_output = array();

		$item = array("name" => "depense", "data" => array($sum_expense), "color"=>"#993333");
		array_push($output,$item);
		$item = array("name" => "revenu", "data" => array($sum_income), "color"=>"#006633");
		array_push($output,$item);

		$options = json_decode("{\"bar\":{\"dataLabels\":{\"enabled\": true}},\"series\":{\"pointWidth\":10,\"pointPadding\": 0.1,\"groupPadding\": 0.1}}", true);

		$title = "Balance";
		$subtitle = $sum_income-$sum_expense." de $date1 à $date2";
		$this->highchart_build_generic($chart, &$json_output, $title, $subtitle, $categories, $output, "<b>{point.y} €</b>", $options);

		print json_encode($json_output, JSON_NUMERIC_CHECK);
	}

	function get_tag_line_chart_highchart($tag_name, $month_first_date, $on_last_month_number)
	{
		$query = "select SUM(amount) as tot, GROUP_CONCAT(transaction.description,' : ', amount SEPARATOR '<br>') as tr_desc, CONCAT(YEAR(DATE_SUB(tdate,INTERVAL $month_first_date DAY)), LPAD(MONTH(DATE_SUB(tdate,INTERVAL $month_first_date DAY)),2,'0')) AS ddate, 
		MONTH(DATE_SUB(tdate,INTERVAL $month_first_date DAY)) as mname,
		YEAR(DATE_SUB(tdate,INTERVAL $month_first_date DAY)) as ydate  
		FROM transaction, transaction_tag, tag WHERE transaction_tag.transaction_id = transaction.id AND transaction_tag.tag_id = tag.id 
		AND transaction.account_id = $this->id AND transaction.splitted = 0 AND tag.name = '$tag_name' GROUP BY YEAR(tdate), MONTH(tdate) ORDER BY tdate DESC LIMIT $on_last_month_number";
		
		$res=do_mysql_query($query );
		if(!$res)
			error(mysql_error());

		$chart = json_decode("{\"type\":\"column\"}", true);
			
		$rows = array();
		$categories=array();
		$output = array();
		$output['data'] = array();
		//$output['type'] = 'line';
		$output['name'] = $tag_name;

		$json_output = array();
		
		$i = 0;
		$tot = 0;
		$avg = 0;
		$res=do_mysql_query($query );

		/* fetch rows in reverse order */
		$index = 0;
		for ($i = mysql_num_rows($res) - 1; $i >= 0; $i--) {
			if (!mysql_data_seek($res, $i)) {
				echo "Cannot seek to row $i: " . mysql_error() . "\n";
				continue;
			}

			if (!($row = mysql_fetch_assoc($res))) {
				continue;
			}

			$rows["name"] = $row["tr_desc"];
			array_push($categories, $row["mname"]."/".$row["ydate"]);
			$rows["y"] = -round($row["tot"]);
			$tot += $rows["y"];
			array_push($output['data'],$rows);

			$index++;
		 }

		/*
		while ($row=mysql_fetch_array($res,MYSQL_ASSOC))
		{
		  $rows["name"] = $row["tr_desc"];
		  array_push($categories, $row["mname"]."/".$row["ydate"]);
		  $rows["y"] = -round($row["tot"]);
		  $tot += $rows["y"];
		  array_push($output['data'],$rows);
		  $i++;
		}
		*/

		if ($index > 0)
		  $avg = round($tot / $index);
		
		//$json_output['series'][0] = $output;
		//$json_output['xAxis'] = $categories;

		$title = "Historique de ".$tag_name;
		$subtitle = $tot."€ sur $index derniers mois, moyenne = $avg"."€";
		$this->highchart_build_generic($chart, &$json_output, $title, $subtitle, $categories, $output, "<b>{point.y} €</b>");

		print json_encode($json_output, JSON_NUMERIC_CHECK);
	}

	function get_account_balance_history_tags($date1, $date2)
	{
		$query = "SELECT distinct(tag.name) FROM transaction, tag, transaction_tag 
			where transaction_tag.transaction_id = transaction.id and transaction_tag.tag_id = tag.id and transaction.account_id = $this->id
			and tdate > now() - INTERVAL 12 MONTH order by name";
		$res=do_mysql_query($query);
		$res_array = array();
		while ($row=mysql_fetch_array($res,MYSQL_ASSOC))
		{
			$res_array[] = $row;
		}

		return $res_array;
	}

	function get_account_balance_history($date1, $date2, $withoutthesetags="")
	{
		$withoutthesetags = stripslashes($withoutthesetags);
		$withoutthesetags = str_replace(",", " AND tags NOT LIKE ", $withoutthesetags);

		$on_last_month_number = 12;

		/*
		$query = "SELECT YEAR(tdate) as ydate, MONTH(tdate) as mdate, 
			round(sum(if(type = 'Income',amount,0)),2) as revenu,
			-round(sum(if(type = 'Expense',amount,0)),2) as depense,
			group_concat(distinct(tag.name)) as tags FROM transaction, tag, transaction_tag 
			where transaction_tag.transaction_id = transaction.id and transaction_tag.tag_id = tag.id and transaction.account_id = $this->id
			and tag.name not in ($withoutthesetags)
			GROUP BY YEAR(tdate), MONTH(tdate) order by tdate DESC limit 12;";
			*/


		$query = "select sb.ydate, sb.mdate, round(sum(if(sb.type = 'Income',sb.amount,0)),2) as revenu, -round(sum(if(sb.type = 'Expense',sb.amount,0)),2) as depense, group_concat((sb.tags)) atags 
		FROM (
			SELECT YEAR(tdate) as ydate, MONTH(tdate) as mdate,amount,`type`, group_concat(distinct(tag.name)) as tags 
			FROM transaction left join transaction_tag on transaction.id = transaction_tag.transaction_id left join tag on tag.id = transaction_tag.tag_id
			WHERE transaction.account_id = $this->id
				AND tdate > now()-interval $on_last_month_number month 
				GROUP BY transaction.id having tags is NULL or tags NOT LIKE '' $withoutthesetags  ORDER BY tdate DESC 
		) as sb  GROUP BY sb.ydate, sb.mdate";

		$res=do_mysql_query($query);

		/* output :
		ydate	mdate				revenu	depense		tags
		2014		4				428.00	-3496.07	FraisFixe,Alexis,Loyer,Nounou,Free,Stockholm2014,B...
		2014		3				5181.39	-8030.99	Essence,Appart,Frais,SupermarchÃ©,Epargne,Sante,Au...
		*/

		$chart = json_decode("{\"type\":\"column\",\"renderTo\":\"container_history_balance\"}", true);
		$xaxis = array("categories" => array());
			
		$rows = array();
		//$categories=array("categories"=>"Balance");
		$output = array();
		//$output['data'] = array($sum_expense, $sum_income);
		//$output['type'] = 'line';
		//$output['name'] = array("depense", "revenu");

		$json_output = array();

		$item1 = array("name" => "depense", "data" => array(), "color"=>"#993333");
		$item2 = array("name" => "revenu", "data" => array(), "color"=>"#006633");

		$total_depense = 0;
		$total_revenu = 0;

		$index = 0;
		for ($i = mysql_num_rows($res) - 1; $i >= 0; $i--) {
			if (!mysql_data_seek($res, $i)) {
				echo "Cannot seek to row $i: " . mysql_error() . "\n";
				continue;
			}

			if (!($row = mysql_fetch_assoc($res))) {
				continue;
			}

			array_push($xaxis["categories"], $row["mdate"]."/".$row["ydate"]);

			$depense = $row["depense"];
			$revenu = $row["revenu"];

			$total_depense += $depense;
			$total_revenu += $revenu;

			array_push($item1["data"], $depense);
			array_push($item2["data"], $revenu);
		}

		array_push($output,$item1);
		array_push($output,$item2);
		

		$title = "Balance des 12 derniers mois";
		$subtitle = "Cumul/moyenne depense/revenu : $total_depense/".round($total_depense/$on_last_month_number,2)." - $total_revenu/".round($total_revenu/$on_last_month_number,2);
		$this->highchart_build_generic($chart, &$json_output, $title, $subtitle, $xaxis, $output, "<b>{point.y} €</b>");

		print json_encode($json_output, JSON_NUMERIC_CHECK);
	}

	function highchart_build_generic($chart_array, $chart_array_info, $i_title, $i_sub_title, $categories, $series, $i_point_format, $plot_options=0)
	{
	  $title = array();
	  $title["text"] = $i_title;

	  $sub_title = array();
	  $sub_title["text"] = $i_sub_title;

	  $tooltip = array();
	  $tooltip["pointFormat"] = $i_point_format;

	  $chart_array_info["chart"] = $chart_array;
	  $chart_array_info["title"] = $title;
	  $chart_array_info["subtitle"] = $sub_title;
	  $chart_array_info["xAxis"] = $categories;
	  if ($plot_options)
	  	$chart_array_info["plotOptions"] = $plot_options;

	  if (isset($series["data"][0]))
	  	$chart_array_info["series"][0] = $series;	  
	  else
	  	$chart_array_info["series"] = $series;	  

	  $chart_array_info["tooltip"] = $tooltip;
	}
	
	function get_solde($reconciled)
	{
		if (!$reconciled)
			$query = "SELECT SUM(amount) as solde FROM `transaction` WHERE account_id = $this->id";
		else 
			$query = "SELECT SUM(amount) as solde FROM `transaction` WHERE account_id = $this->id AND status = 'Reconciled'";
		$res=do_mysql_query($query );
		if(!$res)
			error(mysql_error());
		$row=mysql_fetch_array($res,MYSQL_ASSOC);
		return $row["solde"] + $this->solde;
	}
  
  function get_solde_at($at_date)
  {
    $query = "SELECT ROUND(SUM(amount),2) as solde FROM `transaction` WHERE account_id = $this->id and tdate <= '$at_date'";
    $res=do_mysql_query($query );
		if(!$res)
			error(mysql_error());
		$row=mysql_fetch_array($res,MYSQL_ASSOC);
		return $row["solde"] + $this->solde;
  }

    function get_sum_between($tr_type, $date1, $date2)
    {
        $query = "SELECT SUM(amount) as total FROM `transaction` WHERE account_id = $this->id 
        AND transaction.tdate>='$date1' AND transaction.tdate<='$date2' 
        AND transaction.`type`='$tr_type'";
        $res=do_mysql_query($query );
		if(!$res)
			error(mysql_error());
		$row=mysql_fetch_array($res,MYSQL_ASSOC);
		return $row["total"];
    }
}
?>

