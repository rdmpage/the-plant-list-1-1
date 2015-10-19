<?php

require_once('lib.php');

$html = file_get_contents('genera.html');

preg_match_all('/genus">(?<genus>.*)<\/i>/Uu', $html, $m);

//print_r($m);

$skip = false;

$skip = true;

$count = 0;
foreach ($m['genus'] as $genus)
{
	if ($genus == 'Baryxylum') // this is where we resume
	{
		$skip = false;
	}

	if (!$skip)
	{


		$prefix = mb_substr($genus, 0, 1);
	
		$dirname = dirname(__FILE__) . '/csv/' . $prefix;
	
		// Ensure cache subfolder exists for this item
		if (!file_exists($dirname))
		{
			$oldumask = umask(0); 
			mkdir($dirname, 0777);
			umask($oldumask);
		}

		$genus = preg_replace('/&nbsp;/Uu', ' ', $genus);
		echo $genus . "\n";
		$url = 'http://www.theplantlist.org/tpl1.1/search?q=' . $genus . '&csv=true';
	
		$csv = get($url);
	
		//echo $csv;
	
		file_put_contents($dirname . '/' . $genus . '.csv', $csv);
	
		if (($count++ % 10) == 0)
		{
			$rand = rand(1000000, 4000000);
			echo '...sleeping for ' . round(($rand / 1000000),2) . ' seconds' . "\n";
			usleep($rand);
		}
	}
	
}

?>
