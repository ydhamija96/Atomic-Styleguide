<?php

//URL: https://bitbucket.org/api/1.0/repositories/ydhamija96/nyu-bitbucket-design-nav/raw/master/CONTENTS/

class BitBucketRepo{
	private $directoryListing = array();
	private $parentURL;
	private $currentLoc = array();
	function __construct($url){
		$this->parentURL = $url;
		$this->directoryListing = $this->getAllContents($this->parentURL, $this->directoryListing);
		print_r($this->directoryListing);
	}
	private function getAllContents($url, $currentDir){
		$returnValue = file_get_contents($url);
		$contents = explode("\n", $returnValue);
		foreach($contents as $content){
			if(substr($content, -1) == '/'){
				$currentDir[substr($content, 0, -1)] = getAllContents($url . $content, $currentDir);
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
			if (array_key_exists($dir, $listing)) {
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
		$oldLoc = $this->currentLoc;
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
			$this->currentLoc = $oldLoc;
			echo "<hr>No such directory.";
		}
		return $this;
	}
}

echo "<pre>";
$repo = new BitBucketRepo('https://bitbucket.org/api/1.0/repositories/ydhamija96/nyu-bitbucket-design-nav/raw/master/CONTENTS/');
$repo->cd('/components/section_1/');

?>