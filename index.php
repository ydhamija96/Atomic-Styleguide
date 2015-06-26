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
		if($path[0] == '/'){
			return (rtrim($this->parentURL, '/') . '/' . ltrim($path, '/'));
		}
		else{
			return (rtrim($this->parentURL, '/') . '/' . ((count($this->currentLoc) > 0) ? trim(implode('/', $this->currentLoc), '/') . '/' : '') . ltrim($path, '/'));
		}
	}
	public function contents($path){
		return file_get_contents($this->link($path));
	}
    public function currentDir(){
        return end(array_values($this->currentLoc));
    }
    public function isDir($path){
        $old = $this->pwd();
        $this->cd($path);
        $result = (end(array_values($this->currentLoc)) == rtrim($path, '/'));
        $this->cd($old);
        return $result;
    }
    private function copyToServer($path, $iter = 0){
    	$path = rtrim($path, '/');
    	$oldLocation = $this->pwd();
    	$this->cd($path);
    	$name = $this->currentDir();
    	if($iter == 0){
    		$date = new DateTime();
			$timestamp = $date->getTimestamp();
	    	mkdir('files' . $timestamp);
	    	chdir('files' . $timestamp);
    		$oldlink = $this->pwd();
    		$this->cd('/');
    		foreach($this->ls() as $item){
    			if(!$this->isDir($item)){
    				file_put_contents('./'.$item, $this->contents($item));
    			}
    		}
    		$this->cd($oldlink);
    	}
    	mkdir($name);
    	chdir($name);
    	foreach($this->ls() as $item){
    		if($this->isDir($item)){
    			$this->copyToServer($path .'/'. $item, ++$iter);
    		}
    		else{
    			file_put_contents('./'.$item, $this->contents($item));
    		}
    	}
    	chdir('../');
    	$this->cd($oldLocation);
    	if($iter==0){
    		chdir('../');
    		return $timestamp;
    	}
    	else{
    		return 0;
    	}
    }

    public function download($path){
    	$timestamp = $this->copyToServer($path);
    	$filename = "files" . $timestamp;
    	exec("zip -r $filename.zip $filename");
    	$fp = fopen("$filename.zip","r");
        //echo fpassthru($fp);
        //unlink("../$filename");
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
			$repo = new BitBucketRepo('https://bitbucket.org/api/1.0/repositories/mricotta/nyu/raw/master/');
		}
    	$repo->cd('/');
    	foreach($repo->ls() as $file){
    		if(!$repo->isDir($file)){
    			if(substr($file, -4) == '.css'){
    				?><style rel="stylesheet" href="<?= $repo->link($file) ?>"></script><?php
    			}
    		}
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
		if(isset($_POST['download']) && isset($_POST['path']) && $_POST['download'] == "TRUE"){
			$repo->download($_POST['path']);
		}
	?>
	<style>
		body > #content > .singleElement > .options{
			margin-top:20px;
		}
		body > #content > .singleElement{
			margin-bottom:75px;
		}
		body > #content{
			width:100%;
			max-width:1170px;
			padding:15px;
			margin:auto;
			margin-top:75px;
		}
		body > #content > .singleElement > .options > .collapse > .well, body > #content > .singleElement > .options > .collapsing > .well{
			margin-top:5px;
			margin-bottom:5px;
		}
		body > #content > .singleElement > .options > .collapse > .well > h3, body > #content > .singleElement > .options > .collapsing > .well > h3{
			margin-top:-3px;
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
	                            foreach($repo->ls() as $item):
	                                ?><li><a href="?path=<?= urlencode($repo->pwd().'/'.$item) ?>"><?= ucfirst($item) ?></a></li><?php
	                            endforeach;
				            	$repo->cd('..');
				            ?>
				          </ul>
				        </li>
			    		<?php 
			    	endforeach;
			    }
		    ?>
		    <?php
		    	$curlink = $repo->pwd();
		    	$repo->cd('..');
		    ?>
            <li><a href="?path=<?= $repo->currentDir() ?>">Go Up <span class="glyphicon glyphicon-chevron-up" aria-hidden="true"></span></a></li>
            <?php
            	$repo->cd($curlink);
            ?>
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
	                            foreach($repo->ls(false) as $item):       //Shows only directories
	                            //foreach($repo->ls() as $item):              //Shows everything
	                                ?><li><a href="?path=<?= urlencode($repo->pwd().'/'.$item) ?>"><?= ucfirst($item) ?></a></li><?php
	                            endforeach;
	                        }
	                        elseif((strpos($repo->pwd(), '/templates') === 0)){
	                            foreach($repo->ls() as $item):
	                                if(!$repo->isDir($item)){                 //Shows only files
	                                    ?><li><a href="?path=<?= urlencode($repo->pwd().'/'.$item) ?>"><?= ucfirst($item) ?></a></li><?php
	                                }
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
                	$counter = 0;
                    foreach($repo->ls() as $item){
                    	if(!$repo->isDir($item)){
                    		++$counter;
                    		echo '<div class="singleElement">';
                    			echo $repo->contents($item);
                    			?>
                    			<div class="options">
                    				<button class="btn btn-primary" type="button">Download</button>
									<button class="btn btn-primary" type="button" data-toggle="collapse" data-target="#code<?= $counter ?>" aria-expanded="false" aria-controls="code<?= $counter ?>">
										Code
									</button>
									<button class="btn btn-primary" type="button" data-toggle="collapse" data-target="#assets<?= $counter ?>" aria-expanded="false" aria-controls="assets<?= $counter ?>">
										Assets
									</button>
									<div class="collapse" id="code<?= $counter ?>">
										<div class="well">
											<h3>Code</h3>
											<?= htmlspecialchars($repo->contents($item)) ?>
										</div>
									</div>
									<div class="collapse" id="assets<?= $counter ?>">
										<div class="well">
											<h3>Assets</h3>
											<?= '' ?>
											A list of assets will be placed here. Clickable to download. More information about BitBucket directory structure is required.
										</div>
									</div>
								</div>
                    			<?php
                    		echo '</div>';
                    	}
                    }
                }
                elseif(strpos($repo->pwd(), '/templates') === 0){
                    echo "<pre>" . $repo->pwd() . ":<br>";
                    print_r($repo->ls());
                    echo "</pre>";
                }
                else{
                    echo "<pre>" . $repo->pwd() . ":<br>";
                    print_r($repo->ls());
                    echo "</pre>";
                }
            }
            else{
                echo $repo->contents($path);
                ?>
            		<div class="options">
            			<form action="<?= "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" ?>" method="POST">
            				<input type="text" name="path" style="display:none;" value="<?= $repo->pwd() ?>" />
            				<input type="text" name="download" style="display:none;" value="TRUE" />
            				<input type="submit" class="btn btn-primary" value="Download"></input>
            			</form>
            		</div>
        		<?php
            }
		?>
	</div>
    <?php
    	$repo->cd('/');
    	foreach($repo->ls() as $file){
    		if(!$repo->isDir($file)){
    			if(substr($file, -3) == '.js'){
    				?><script src="<?= $repo->link($file) ?>"></script><?php
    			}
    		}
    	}
	?>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <script src="http://getbootstrap.com/assets/js/ie10-viewport-bug-workaround.js"></script>
    <script>
		$(function () {
			$('[data-toggle="popover"]').popover()
		})
    </script>
</body>

</html>