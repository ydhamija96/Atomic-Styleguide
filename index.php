<?php


$directoryListing = array();
$url = 'https://bitbucket.org/api/1.0/repositories/ydhamija96/nyu-bitbucket-design-nav/raw/master/';



function findDir($url, $currentDir){
	$returnValue = file_get_contents($url);
	$contents = explode("\n", $returnValue);
	foreach($contents as $content){
		if(substr($content, -1) == '/'){
			$currentDir[substr($content, 0, -1)] = findDir($url . $content, $currentDir);
		}
		else{
			$currentDir[$content] = returnFileContents($url.$content);
		}
	}
	return $currentDir;
}

function returnFileCOntents($url){
	return $url;
}

$directoryListing = findDir($url, $directoryListing);

echo $directoryListing["CONTENTS"]["test.html"];

?>