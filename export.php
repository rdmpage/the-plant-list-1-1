<?php

require_once (dirname(__FILE__) . '/adodb5/adodb.inc.php');
require_once (dirname(__FILE__) . '/fetch/lib.php');

//--------------------------------------------------------------------------------------------------
$db = NewADOConnection('mysql');
$db->Connect("localhost", 
	'root', '', 'ipni');

// Ensure fields are (only) indexed by column name
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$db->EXECUTE("set names 'utf8'"); 


//--------------------------------------------------------------------------------------------------
function get_reference($guid)
{
	$url = 'http://localhost/~rpage/microcitation/www/gbif.php?guid=' . $guid;
	
	$json = get($url);
	
	$obj = json_decode($json);
	
	return $obj;
}


//--------------------------------------------------------------------------------------------------
function get_ipni($ID, $namePublishedIn, $ipni_id)
{
	global $db;
	
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
			$reference = get_reference($guid);
			
			if ($reference)
			{
				echo $ID;
				echo "\t" . $reference[0];
				echo "\t" . $namePublishedIn;
				
				array_shift($reference);
				
				echo "\t" . join("\t", $reference);
				echo "\n";
			}
		}
	
	
	}
}

//--------------------------------------------------------------------------------------------------

$mode = 0; // taxa
//$mode = 1; // references


$count = 0;

$page = 10;
$offset = 0;

$result = $db->Execute('SET max_heap_table_size = 1024 * 1024 * 1024');
$result = $db->Execute('SET tmp_table_size = 1024 * 1024 * 1024');


$done = false;

while (!$done)
{
	$sql = 'SELECT * FROM `tpl1-1` LIMIT ' . $page . ' OFFSET ' . $offset;
	$sql = 'SELECT * FROM `tpl1-1` WHERE Genus="Begonia" LIMIT ' . $page . ' OFFSET ' . $offset;

	$result = $db->Execute($sql);
	if ($result == false) die("failed [" . __FILE__ . ":" . __LINE__ . "]: " . $sql);

	while (!$result->EOF && ($result->NumRows() > 0)) 
	{	
		$taxon_row = array();
	
		$taxon_row[] = $result->fields['ID'];
		
		$ID = $result->fields['ID'];
		

		// acceptedNameUsageID
		$taxon_row[] = $result->fields['AcceptedID'];
		// taxonomicStatus
		$taxon_row[] = $result->fields['Taxonomic_status_in_TPL'];
		
		// phylum (ignore for now as not always monophyletic)
		// A = Angiosperms
		// G = Gymnosperms
		// B = Bryophytes (Mosses and liverworts)
		// P = Pteridophytes (Ferns and fern allies)
		// _ ?

		// family
		$taxon_row[] = $result->fields['Family'];

		// Name elements

		// genus
		$taxon_row[] = $result->fields['Genus'];
		// specificEpithet
		$taxon_row[] = $result->fields['Species'];
		// infraspecificEpithet
		$taxon_row[] = $result->fields['Infraspecific_epithet'];
		
		
		// construct a name (and rank)
		$rank = '';
		$name = '';

		if (trim($result->fields['Genus_hybrid_marker']) != '')
		{
			$name .= ' ' . $result->fields['Genus_hybrid_marker'] . ' ';
		}

		if (trim($result->fields['Genus']) != '')
		{
			$rank = 'genus';
			$name = $result->fields['Genus'];
		}

		if (trim($result->fields['Species_hybrid_marker']) != '')
		{
			$name .= ' ' . $result->fields['Species_hybrid_marker'];
		}

		if (trim($result->fields['Species']) != '')
		{
			$rank = 'species';
			$name .= ' ' . $result->fields['Species'];
		}

		if (trim($result->fields['Infraspecific_rank']) != '')
		{
			switch ($result->fields['Infraspecific_rank'])
			{
				case 'subsp.':
					$rank = 'subspecies';
					break;
				case 'var.':
					$rank = 'variety';
					break;
				default:
					$rank = $result->fields['Infraspecific_rank'];
					break;
			}

			$name .= ' ' . $result->fields['Infraspecific_rank'];
		}

		if (trim($result->fields['Infraspecific_epithet']) != '')
		{
			$name .= ' ' . $result->fields['Infraspecific_epithet'];
		}

		// 
		$taxon_row[] = $name;
		$taxon_row[] = $rank;
		
		// scientificNameAuthorship
		$taxon_row[] = $result->fields['Authorship'];

		// sources
		// nameAccordingTo
		$taxon_row[] = $result->fields['Source'];
		// nameAccordingToID
		$taxon_row[] = $result->fields['Source_id'];

		// scientificNameID (IPNI)
		if (trim($result->fields['IPNI_id'] != ''))
		{
			$taxon_row[] =  'urn:lsid:ipni.org:names:' . $result->fields['IPNI_id'];
		}
		else
		{
			$taxon_row[] = '';
		}
			
		// Publication
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
		$taxon_row[] =  join(", ", $parts);
		
		$namePublishedIn = join(", ", $parts);
		
		if ($mode == 0)
		{
			if ($count == 0)
			{
				$headings = array(
					'taxonID', 
					'acceptedNameUsageID',
					'taxonomicStatus',
					'family',
					'genus',
					'specificEpithet',
					'infraspecificEpithet',
					'scientificName',
					'taxonRank',
					'scientificNameAuthorship',
					'nameAccordingTo',
					'nameAccordingToID',
					'scientificNameID',
					'namePublishedIn'
				);
					
				echo join ("\t", $headings) . "\n";		
	
			}		
		
			echo join ("\t", $taxon_row) . "\n";
		}
		
		if ($mode == 1)
		{
			if ($count == 0)
			{
				$headings = array('taxonID', 'identifier', 'bibliographicCitation', 'title', 'creator', 'date', 'source', 'type');
				echo join ("\t", $headings) . "\n";
			}
			// Publication identifier(s)
			$ipni = $result->fields['IPNI_id'];
			if ($ipni != '')
			{
				get_ipni($ID, $namePublishedIn, $ipni);
			}
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
		
		if ($offset > 1000) { $done = true; }
	}
	
	
}



?>