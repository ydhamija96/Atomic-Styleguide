<?php
	require_once('BitBucketRepo.php');
	session_start();
	if(isset($_SESSION['repo'])){
		$repo = $_SESSION['repo'];
		if(isset($_POST['action']) && isset($_POST['path']) && isset($_POST['input']) && $_POST['action'] == 'relevantCSS'){
			echo json_encode(array('Input' => $_POST['input'], 'Output' => htmlspecialchars($repo->relevantCSS($_POST['path']))));
		}
		if(isset($_POST['action']) && isset($_POST['path']) && $_POST['action'] == 'relevantHTML'){
			echo json_encode(array('Input' => $_POST['input'], 'Output' => htmlspecialchars($repo->contents($_POST['path']))));
		}
		if(isset($_POST['action']) && isset($_POST['path']) && $_POST['action'] == 'relevantAssets'){
			echo json_encode(array('Input' => $_POST['input'], 'Output' => $repo->relevantDownloads($_POST['path'])));
		}
	}
?>