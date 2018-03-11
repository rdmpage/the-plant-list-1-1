<?php

// Dump data for datrwin core

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
		$taxon_row = array();
	
		$taxon_row[] = $result->fields['ID'];
		
		$ID = $result->fields['ID'];
		

		// acceptedNameUsageID
		$taxon_row[] = $result->fields['AcceptedID'];
		// taxonomicStatus
		
		$status = strtolower($result->fields['Taxonomic_status_in_TPL']);
		switch ($status)
		{
			case 'unresolved':
				$status = 'doubtful';
				break;
				
			default:
				break;
		}
		$taxon_row[] = $status;
		
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
		
		$namePublishedIn = $result->fields['namePublishedIn'];
		$namePublishedInID = $result->fields['namePublishedInID'];

		$taxon_row[] = $namePublishedIn;	
		$taxon_row[] = $namePublishedInID;	
			
		
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
					'namePublishedIn',
					'references'
				);
					
				echo join ("\t", $headings) . "\n";		
	
			}		
		
			echo join ("\t", $taxon_row) . "\n";
		}
		
		if ($mode == 1)
		{
			/*
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
			*/
			
			// More detailed but unparsed bibliography
			if ($count == 0)
			{
				$headings = array('taxonID', 'identifier', 'bibliographicCitation');
				echo join ("\t", $headings) . "\n";
			}
			// Publication identifier(s)
			if ($namePublishedInID != '')
			{
				$reference_row = array();
				$reference_row[] = $ID;
				$reference_row[] = $namePublishedInID;
				$reference_row[] = $namePublishedIn;
				
				echo join ("\t", $reference_row) . "\n";
				
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
		
		//if ($offset > 3000) { $done = true; }
	}
	
	
}



?>