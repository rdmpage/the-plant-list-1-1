<?php

// Generate a SQL file of references based on mapping IPNI_id to my IPNI names project.
// Relies of microcitation database web service
// Resulting SQL can be added to TPL 1.1 MySQL table

require_once (dirname(__FILE__) . '/adodb5/adodb.inc.php');
require_once (dirname(__FILE__) . '/fetch/lib.php');

//--------------------------------------------------------------------------------------------------
$db = NewADOConnection('mysql');
$db->Connect("localhost", 
	'root', '', 'ipni');

// Ensure fields are (only) indexed by column name
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$db->EXECUTE("set names 'utf8'"); 


//----------------------------------------------------------------------------------------
function get_formatted_reference($ipni_id)
{
	global $db;
	
	$citation = '';

	$sql = 'SELECT * FROM names WHERE Id="' . $ipni_id . '" LIMIT 1';
	$result = $db->Execute($sql);
	if ($result == false) die("failed [" . __FILE__ . ":" . __LINE__ . "]: " . $sql);

	if ($result->NumRows() == 1)
	{	
		$identifier = '';
	
		$guid = '';
	
		if ($result->fields['doi'] != '')
		{
			$guid = $result->fields['doi'];
		}
		
		if ($guid == '')
		{
			if ($result->fields['jstor'] != '')
			{
				$guid = 'http://www.jstor.org/stable/' . $result->fields['jstor'];
			}		
		}
		
		
		if ($guid != '')
		{
			$url = 'http://localhost/~rpage/microcitation/www/darwincore.php?guid=' . $guid;
	
			$json = get($url);
			//echo $json;
	
			$obj = json_decode($json);
		
		
			$citation = $obj->html;
		}	
	
	}
	
	return $citation;
}



//----------------------------------------------------------------------------------------
function get_ipni_references_link($ipni_id)
{
	global $db;
	
	$link = '';
	
	$sql = 'SELECT * FROM names WHERE Id="' . $ipni_id . '" LIMIT 1';
	
	//echo "$sql\n";
	
	$result = $db->Execute($sql);
	if ($result == false) die("failed [" . __FILE__ . ":" . __LINE__ . "]: " . $sql);

	if ($result->NumRows() == 1)
	{	
	
		if ($result->fields['doi'] != '')
		{
			$link = 'http://doi.org/' . $result->fields['doi'];
		}
		
		if ($link == '')
		{
			if ($result->fields['jstor'] != '')
			{
				$link = 'http://www.jstor.org/stable/' . $result->fields['jstor'];
			}		
		}
	
	}
	
	return $link;
}



//--------------------------------------------------------------------------------------------------

$mode = 0; // taxa
$mode = 1; // references


$count = 0;

$page = 1000;
$offset = 0;

$result = $db->Execute('SET max_heap_table_size = 1024 * 1024 * 1024');
$result = $db->Execute('SET tmp_table_size = 1024 * 1024 * 1024');


$done = false;

while (!$done)
{
	$sql = 'SELECT * FROM `tpl1-1` LIMIT ' . $page . ' OFFSET ' . $offset;
	//$sql = 'SELECT * FROM `tpl1-1` WHERE Genus="Begonia" LIMIT ' . $page . ' OFFSET ' . $offset;

	$result = $db->Execute($sql);
	if ($result == false) die("failed [" . __FILE__ . ":" . __LINE__ . "]: " . $sql);

	while (!$result->EOF && ($result->NumRows() > 0)) 
	{			
		$ID = $result->fields['ID'];
		

		// Publication based on TPL1-1
		// namePublishedIn
		$keys = array('Publication', 'Collation', 'Page', 'Date');
		$parts = array();
		foreach ($keys as $k)
		{
			$v = trim($result->fields[$k]);
			if ($v != '')
			{
				$parts[] = $v;
			}
		}
		$namePublishedIn = join(", ", $parts);
		
		// OK, can we do better by linking via IPNI?
		if (trim($result->fields['IPNI_id'] != ''))
		{		
			$namePublishedIn = get_formatted_reference($result->fields['IPNI_id']);
		}
		
		$namePublishedInID = '';
		if (trim($result->fields['IPNI_id'] != ''))
		{
			$namePublishedInID = get_ipni_references_link($result->fields['IPNI_id']);
		}
		
		
		
		if ($namePublishedIn != '')
		{
			$sql = 'UPDATE `tpl1-1` SET namePublishedIn = "' . addcslashes($namePublishedIn, '"') . '"';
			
			if ($namePublishedInID != '')
			{
				$sql .= ', namePublishedInID="' . $namePublishedInID . '"';
			}
			$sql .= ' WHERE ID="' . $ID . '";';
		
			echo $sql;
			echo "\n";
		}
		

		
		
		//echo "\n";
		
		$count++;

		$result->MoveNext();
	}
	
	//echo "-------\n";
	
	if ($result->NumRows() < $page)
	{
		$done = true;
	}
	else
	{
		$offset += $page;
		
		//if ($offset > 3000) { $done = true; }
	}
	
	
}



?>