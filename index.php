<?php


$directoryListing = array();
$url = 'https://bitbucket.org/api/1.0/repositories/ydhamija96/nyu-bitbucket-design-nav/raw/master/';



function findDir($url, $currentDir){
	$returnValue = file_get_contents($url);		// = "CONTENT/ index.php"
	$contents = explode("\n", $returnValue);	// = ["CONTENT/", "index.php"]
	foreach($contents as $content){
		if($content[ count($content)-1 ] == '/'){	//Is a directory
			echo "DIR";
			$currentDir[$content] = findDir($url . $content, $currentDir[$content]);	// = ['CONTENT/' => [...], ...]
		}
		else{	//Is a file
			$currentDir['FILE'] = $content;
		}
	}
	return $currentDir;
}

findDir($url, $directoryListing);

echo "<pre>";
print_r($directoryListing);

?>