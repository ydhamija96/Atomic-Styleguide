<?php
	require_once('BitBucketRepo.php');
	session_start();
	
	if(!isset($_GET['url']) || !isset($_GET['nonce'])){
		echo "gtfo";
		exit;
		die();
	}

	if(!isset($_SESSION['nonce']) || !isset($_SESSION['repo']) || $_SESSION['nonce'] != $_GET['nonce']){
		echo "gtfo";
		exit;
		die();
	}

	$repo = $_SESSION['repo'];

	$url = strrev($_GET['url']);

	// Get the extension
    preg_match('/\.[^.]+?$/', $url, $extension);
    $extension = $extension[0];

	$file = preg_replace("([^\w])", '-', $url);
	$file = $file.$extension;
	$file = 'resources/'.$file;

    // Download the file
	if(!file_exists($file)){
		if($extension == '.html' || $extension == '.js'){
			file_put_contents($file, $repo->fixRelatives(file_get_contents($url)));
		}
		elseif($extension == '.css'){
			file_put_contents($file, $repo->fixCSS(file_get_contents($url)));
		}
		else{
    		file_put_contents($file, file_get_contents($url));
    	}
    }

    header('Location: '.$file);

?>