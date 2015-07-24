<?php
	require_once('BitBucketRepo.php');
	session_start();
	if(isset($_SESSION['repo'])){
		$repo = $_SESSION['repo'];
		if(isset($_POST['action']) && isset($_POST['path']) && $_POST['action'] == 'relevantCSS'){
			echo htmlspecialchars($repo->relevantCSS($_POST['path']));
		}
		if(isset($_POST['action']) && isset($_POST['path']) && $_POST['action'] == 'relevantHTML'){
			echo htmlspecialchars($repo->contents($_POST['path']));
		}
		if(isset($_POST['action']) && isset($_POST['path']) && $_POST['action'] == 'relevantAssets'){
			echo $repo->relevantDownloads($_POST['path']);
		}
	}
?>