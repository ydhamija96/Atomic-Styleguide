<?php
	session_start();
	
	if(!isset($_GET['url']) || !isset($_GET['nonce'])){
		echo "gtfo";
		exit;
		die();
	}

	if(!isset($_SESSION['nonce']) || $_SESSION['nonce'] != $_GET['nonce']){
		echo "gtfo";
		exit;
		die();
	}

	$url = strrev($_GET['url']);

	// Get the extension
    preg_match('/\.[^.]+?$/', $url, $extension);
    $extension = $extension[0];

	$file = preg_replace("([^\w])", '-', $url);
	$file = $file.$extension;
	$file = 'resources/'.$file;

    // Download the file
	if(!file_exists($file)){
    	file_put_contents($file, file_get_contents($url));
    }

    header('Location: '.$file);

?>