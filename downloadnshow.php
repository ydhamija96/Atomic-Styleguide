<?php
	if(!isset($_GET['url'])){
		echo "gtfo";
		exit;
	}

	// Security Vulnerability!

	$url = strrev($_GET['url']);

	// Get the extension
    preg_match('/\.[^.]+?$/', $url, $extension);
    $extension = $extension[0];

	$file = preg_replace("([^\w])", '-', $url);
	$file = $file.$extension;
	$file = 'resources/'.$file;

    // Download the file
	if(!file_exists($file) && $extension != '.css' && $extension != '.js'){
    	file_put_contents($file, file_get_contents($url));
    }

    header('Location: '.$file);

?>