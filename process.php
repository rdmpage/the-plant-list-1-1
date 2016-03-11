<?php

// Merge harvested CSV files into a single TSV file that we can import into MySQL

//----------------------------------------------------------------------------------------
// http://stackoverflow.com/a/5996888/9684
function translate_quoted($string) {
  $search  = array("\\t", "\\n", "\\r");
  $replace = array( "\t",  "\n",  "\r");
  return str_replace($search, $replace, $string);
}

$mode = 0; // DarwinCore
//$mode = 1; // IPNI join dump
$mode = 2; // SQL dump

$ignoreHeaderLines = 1;
$fieldsTerminatedBy = ",";
$fieldsEnclosedBy = "\"";

$basedir = 'fetch/csv';

$folders = scandir(dirname(__FILE__) . '/' . $basedir);
//print_r($folders);exit();

foreach ($folders as $folder)
{
	if (!preg_match('/^\./', $folder))
	{
		$files = scandir(dirname(__FILE__) . '/' . $basedir . '/' . $folder);
		foreach ($files as $filename)
		{
			if (preg_match('/\.csv$/', $filename))
			{	
				$filename = dirname(__FILE__) . '/' . $basedir . '/' . $folder . '/' . $filename;


				//$filename = '/Users/rpage/Development/the-plant-list-1-1/fetch/csv/N/Nothofagus.csv';			
				//$filename = '/Users/rpage/Development/the-plant-list-1-1/fetch/csv/B/Begonia.csv';		
				//$filename = '/Users/rpage/Development/the-plant-list-1-1/fetch/csv/R/Rafflesia.csv';		
							
				//echo $filename . "\n";
			
				// Grab
			
				$row_count = 0;
			
				$headings = array();
				$heading_key = array();
	
				$file = @fopen($filename, "r") or die("couldn't open $filename");
			
				$file_handle = fopen($filename, "r");
				while (!feof($file_handle)) 
				{
					$row = fgetcsv(
						$file_handle, 
						0, 
						translate_quoted($fieldsTerminatedBy),
						(translate_quoted($fieldsEnclosedBy) != '' ? translate_quoted($fieldsEnclosedBy) : '"') 
						);
					
					//print_r($row);

					$go = is_array($row);
				
					if ($go && ($row_count == 0) && ($ignoreHeaderLines == 1))
					{			
	/*
	Array
	(
		[0] => ﻿ID
		[1] => Major group
		[2] => Family
		[3] => Genus hybrid marker
		[4] => Genus
		[5] => Species hybrid marker
		[6] => Species
		[7] => Infraspecific rank
		[8] => Infraspecific epithet
		[9] => Authorship
		[10] => Taxonomic status in TPL
		[11] => Nomenclatural status from original data source
		[12] => Confidence level
		[13] => Source
		[14] => Source id
		[15] => IPNI id
		[16] => Publication
		[17] => Collation
		[18] => Page
		[19] => Date
		[20] => Accepted ID
	)
	*/				
				
						$headings = $row;
					
						// http://stackoverflow.com/a/15423899
						// Handle BOM
						$bom = pack('H*','EFBBBF');
	
						for ($j = 0; $j < count($row); $j++)
						{
							$heading = $headings[$j];
							$heading = preg_replace("/^$bom/", '', $heading);
						
							$heading_key[$heading] = $j;
						}
					
						$go = false;
					}
					if ($go)
					{			
				
						// ID,Major group,Family,Genus hybrid marker,Genus,Species hybrid marker,Species,Infraspecific rank,Infraspecific epithet,Authorship,Taxonomic status in TPL,Nomenclatural status from original data source,Confidence level,Source,Source id,IPNI id,Publication,Collation,Page,Date,Accepted ID
					
						switch ($mode)
						{
							case 0: // Darwin Core taxon
		
								// taxonID
								echo $row[$heading_key["ID"]];
					
								// acceptedNameUsageID
								echo "\t" . $row[$heading_key["Accepted ID"]];
								// taxonomicStatus
								echo "\t" . $row[$heading_key["Taxonomic status in TPL"]];
					
								// family
								echo "\t" . $row[$heading_key['Family']];
					
								// Name elements
					
					
					
					
								// genus
								echo "\t" . $row[$heading_key['Genus']];
								// specificEpithet
								echo "\t" . $row[$heading_key['Species']];
								// infraspecificEpithet
								echo "\t" . $row[$heading_key['Infraspecific epithet']];
					
								// construct a name (and rank)
								$rank = '';
								$name = '';
					
								if (trim($row[$heading_key['Genus hybrid marker']]) != '')
								{
									$name .= ' ' . $row[$heading_key['Genus hybrid marker']] . ' ';
								}
					
								if ($row[$heading_key['Genus']] != '')
								{
									$rank = 'genus';
									$name = $row[$heading_key['Genus']];
								}
					
								if (trim($row[$heading_key['Species hybrid marker']]) != '')
								{
									$name .= ' ' . $row[$heading_key['Species hybrid marker']];
								}

								if (trim($row[$heading_key['Species']]) != '')
								{
									$rank = 'species';
									$name .= ' ' . $row[$heading_key['Species']];
								}

								if (trim($row[$heading_key['Infraspecific rank']]) != '')
								{
									switch ($row[$heading_key['Infraspecific rank']])
									{
										case 'subsp.':
											$rank = 'subspecies';
											break;
										case 'var.':
											$rank = 'variety';
											break;
										default:
											$rank = $row[$heading_key['Infraspecific rank']];
											break;
									}
						
									$name .= ' ' . $row[$heading_key['Infraspecific rank']];
								}

								if (trim($row[$heading_key['Infraspecific epithet']]) != '')
								{
									$name .= ' ' . $row[$heading_key['Infraspecific epithet']];
								}
					
								// 
								echo "\t" . $name;
								echo "\t" . $rank;
					
								// scientificNameAuthorship
								echo "\t" . $row[$heading_key['Authorship']];
					
								// sources
								// nameAccordingTo
								echo "\t" . $row[$heading_key['Source']];
								// nameAccordingToID
								echo "\t" . $row[$heading_key['Source id']];
					
								// scientificNameID (IPNI)
								echo "\t";
								if (trim($row[$heading_key['IPNI id']] != ''))
								{
									echo 'urn:lsid:ipni.org:names:' . $row[$heading_key['IPNI id']];
								}
					
								// namePublishedIn
								$keys = array('Publication', 'Collation', 'Page', 'Date');
								$parts = array();
								foreach ($keys as $k)
								{
									$v = trim($row[$heading_key[$k]]);
									if ($v != '')
									{
										$parts[] = $v;
									}
								}
								echo "\t" . join(", ", $parts);
					
								echo "\n";
								break;
							
							case 1:
								if (trim($row[$heading_key['IPNI id']] != ''))
								{
									echo $row[$heading_key["ID"]] . "\t" . $row[$heading_key['IPNI id']] . "\n";
								}
								break;
						
							// TSV export
							case 2:
								echo join("\t", $row) . "\n";
								break;	
							
							default:
								break;
						}
					
					}
				
				
				
					$row_count++;
				}
			}
		}
	}
}

	
?>