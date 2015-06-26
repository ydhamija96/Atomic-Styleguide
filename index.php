<?php
class BitBucketRepo{
	private $directoryListing = array();
	private $parentURL;
	private $currentLoc = array();
	function __construct($url){
		$this->parentURL = $url;
		$this->directoryListing = $this->getAllContents($this->parentURL, $this->directoryListing);
	}
	private function getAllContents($url, $currentDir){
		$returnValue = file_get_contents($url);
		$contents = explode("\n", $returnValue);
		foreach($contents as $content){
			if(substr($content, -1) == '/'){
				$currentDir[substr($content, 0, -1)] = array();
				$currentDir[substr($content, 0, -1)] = $this->getAllContents($url . $content, $currentDir[substr($content, 0, -1)]);
			}
			else{
				$currentDir[$content] = $url.$content;
			}
		}
		return $currentDir;
	}
	private function inListing($dirs) {
		$listing = $this->directoryListing;
		foreach ($dirs as $dir) {
			if (array_key_exists($dir, $listing) && is_array($listing[$dir])) {
				$listing = $listing[$dir];
			}
			else{
				return false;
			}
		}
		return true;
	}
	public function pwd(){
		return '/'.implode('/', $this->currentLoc);
	}
	public function cd($loc){
		if($loc == '/'){
			$this->currentLoc = array();
			return $this;
		}
		$loc = (strlen($loc) > 1) ? rtrim($loc, '/') : $loc;
		$loc = explode('/', $loc);
		if($loc[0] == ''){
			$this->currentLoc = array_slice($loc, 1);
			foreach($this->currentLoc as $i => $dir){
				if($dir == '..'){
					array_splice($this->currentLoc, $i-1, 2);
				}
			}
		}
		else{
			$this->currentLoc = array_merge($this->currentLoc, $loc);
			foreach($this->currentLoc as $i => $dir){
				if($dir == '..'){
					array_splice($this->currentLoc, $i-1, 2);
				}
			}
		}
		if(!$this->inListing($this->currentLoc)){
			$this->cd('..');
		}
		return $this;
	}
	public function ls($all = true){
		$listing = $this->directoryListing;
		$result = array();
		foreach ($this->currentLoc as $dir) {
			$listing = $listing[$dir];
		}
		foreach($listing as $key => $item){
			if(is_array($item)){
				$result[] = $key.'/';
			}
			elseif($all){
				$result[] = $key;
			}
		}
		return $result;
	}
	public function link($path){
		return (rtrim($this->parentURL, '/') . '/' . ((count($this->currentLoc) > 0) ? trim(implode('/', $this->currentLoc), '/') . '/' : '') . ltrim($path, '/'));
	}
	public function contents($path){
		return file_get_contents($this->link($path));
	}
    public function parentDir(){
        return implode('/', array_slice($this->currentLoc,0,-1));
    }
    public function isDir($path){
        $old = $this->pwd();
        $this->cd($path);
        $result = (end(array_values($this->currentLoc)) == rtrim($path, '/'));
        $this->cd($old);
        return $result;
    }
}
?>


<?php session_start(); ?>
<!DOCTYPE html>
<html>

<head>
	<title>NYU Atomic Styleguide</title>
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
	<?php
		if(isset($_SESSION['repo'])){
			$repo = $_SESSION['repo'];
		}
		else{
			$repo = new BitBucketRepo('https://bitbucket.org/api/1.0/repositories/ydhamija96/nyu-bitbucket-design-nav/raw/master/CONTENTS/');
		}
		$repo->cd('/');
		if(in_array('master.css', $repo->ls()) && !$repo->isDir('master.css')){
			?><link rel="stylesheet" href="<?= $repo->link('master.css') ?>"><?php
		}
		if(isset($_GET['path']) && trim($_GET['path']) != ''){
			$path = urldecode($_GET['path']);
			$repo->cd($path);
            $singleFile = !(trim($repo->pwd(), '/') == trim($path, '/'));
		}
		else{
			$repo->cd('/');
            $singleFile = false;
		}
	?>
	<style>
		#singleElement{
			margin-bottom:25px;
		}
		#content{
			width:100%;
			max-width:1170px;
			padding:15px;
			margin:auto;
			margin-top:75px;
		}
	</style>
</head>

<body>
	<nav class="navbar navbar-inverse navbar-fixed-top">
	  <div class="container">
	    <div class="navbar-header">
	      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
	        <span class="sr-only">Toggle navigation</span>
	        <span class="icon-bar"></span>
	        <span class="icon-bar"></span>
	        <span class="icon-bar"></span>
	      </button>
	      <a class="navbar-brand" href="?">Style Guide</a>
	    </div>
	    <div id="navbar" class="navbar-collapse collapse">
	      <ul class="nav navbar-nav">
            <li class="active"><a>Current Path: <?= $repo->pwd() ?></a></li>
            <?php
            	if($repo->pwd() != '/'){
		        	foreach($repo->ls(false) as $dir): ?>
		        		<li class="dropdown">
				          <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><?= ucfirst(rtrim($dir, '/')) ?><span class="caret"></span></a>
				          <ul class="dropdown-menu">
				            <?php 
				            	$repo->cd($dir);
				            	if((strpos($repo->pwd(), '/components') === 0)){
		                            //foreach($repo->ls(false) as $item):       //Shows only directories
		                            foreach($repo->ls() as $item):              //Shows everything
		                                ?><li><a href="?path=<?= urlencode($repo->pwd().'/'.$item) ?>"><?= ucfirst($item) ?></a></li><?php
		                            endforeach;
		                        }
		                        elseif((strpos($repo->pwd(), '/templates') === 0)){
		                            foreach($repo->ls() as $item):
		                                //if(!$repo->isDir($item)){                 //Shows only files
		                                    ?><li><a href="?path=<?= urlencode($repo->pwd().'/'.$item) ?>"><?= ucfirst($item) ?></a></li><?php
		                                //}
		                            endforeach;                            
		                        }
		                        else{
		                            foreach($repo->ls() as $item):
		                                ?><li><a href="?path=<?= urlencode($repo->pwd().'/'.$item) ?>"><?= ucfirst($item) ?></a></li><?php
		                            endforeach;                            
		                        }
				            	$repo->cd('..');
				            ?>
				          </ul>
				        </li>
			    		<?php 
			    	endforeach;
			    }
		    ?>
            <li><a href="?path=<?= $repo->parentDir() ?>">Go Up <span class="glyphicon glyphicon-chevron-up" aria-hidden="true"></span></a></li>
          </ul>
	      <ul class="nav navbar-nav navbar-right">
	        <?php
	        	$current = $repo->pwd();
	        	$repo->cd('/');
	        	foreach($repo->ls(false) as $dir): ?>
	        		<li class="dropdown">
			          <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><?= ucfirst(rtrim($dir, '/')) ?><span class="caret"></span></a>
			          <ul class="dropdown-menu">
			            <?php 
			            	$repo->cd($dir);
			            	if((strpos($repo->pwd(), '/components') === 0)){
	                            //foreach($repo->ls(false) as $item):       //Shows only directories
	                            foreach($repo->ls() as $item):              //Shows everything
	                                ?><li><a href="?path=<?= urlencode($repo->pwd().'/'.$item) ?>"><?= ucfirst($item) ?></a></li><?php
	                            endforeach;
	                        }
	                        elseif((strpos($repo->pwd(), '/templates') === 0)){
	                            foreach($repo->ls() as $item):
	                                //if(!$repo->isDir($item)){                 //Shows only files
	                                    ?><li><a href="?path=<?= urlencode($repo->pwd().'/'.$item) ?>"><?= ucfirst($item) ?></a></li><?php
	                                //}
	                            endforeach;                            
	                        }
	                        else{
	                            foreach($repo->ls() as $item):
	                                ?><li><a href="?path=<?= urlencode($repo->pwd().'/'.$item) ?>"><?= ucfirst($item) ?></a></li><?php
	                            endforeach;                            
	                        }
			            	$repo->cd('..');
			            ?>
			          </ul>
			        </li>
		    		<?php 
		    	endforeach;
		    	$repo->cd($current); 
		    ?>
	      </ul>
	    </div>
	  </div>
	</nav>
	<div id="content">
		<?php
            if(!$singleFile){
                if(strpos($repo->pwd(), '/components') === 0){
                    foreach($repo->ls() as $item){
                    	if(!$repo->isDir($item)){
                    		echo '<div id="singleElement">';
                    			echo $repo->contents($item);
                    		echo '</div>';
                    	}
                    }
                }
                elseif(strpos($repo->pwd(), '/templates') === 0){
                    foreach($repo->ls() as $item){
                    	if(!$repo->isDir($item)){
                    		echo $repo->contents($item);
                    	}
                    }
                }
                else{
                    echo "<pre>" . $repo->pwd() . ":<br>";
                    print_r($repo->ls());
                    echo "</pre>";
                }
            }
            else{
                echo $repo->contents($path);
            }
		?>
	</div>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <script src="http://getbootstrap.com/dist/js/bootstrap.min.js"></script>
    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <script src="http://getbootstrap.com/assets/js/ie10-viewport-bug-workaround.js"></script>
    <?php
    	$repo->cd('/');
		if(in_array('scripts.js', $repo->ls()) && !$repo->isDir('scripts.js')){
			?><script src="<?= $repo->link('scripts.js') ?>"></script><?php
		}
	?>
</body>

</html>