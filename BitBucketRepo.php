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
    public function ls($all = true, $recursive = false){
        $listing = $this->directoryListing;
        $result = array();
        foreach ($this->currentLoc as $dir) {
            $listing = $listing[$dir];
        }
        foreach($listing as $key => $item){
            if(is_array($item)){
                if($recursive){
                    $oldlocation = $this->pwd();
                    $this->cd($key);
                    $additionalResults = $this->ls($all, true);
                    foreach($additionalResults as $single){
                        $single = $key.'/'.$single;
                        $result[] = $single;
                    }
                    $this->cd($oldlocation);
                }
                else{
                    $result[] = $key.'/';
                }
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
    private function copyToServer($item){
        // Create a directory:
        $date = new DateTime();
        $stamp = $date->getTimestamp();
        $rootname = "download_".$stamp;
        mkdir($rootname);
        
        // Get name of file to be downloaded:
        $filename = explode('/', $item);
        $filename = end(array_values($filename));
        
        // Create a directory and put the actual item in it:
        $foldername = substr($filename, 0, -5);    // Takes out the .html
        mkdir($rootname.'/'.$foldername);
        file_put_contents($rootname.'/'.$foldername.'/'.$filename, $this->contents($item));
        
        // Put root .css and .js in it:
        $oldlocation = $this->pwd();
        $this->cd('/');
        foreach($this->ls() as $file){
            if(!$this->isDir($file)){
                if(substr($file, -3) == '.js' || substr($file, -4) == '.css'){
                    file_put_contents($rootname.'/'.$foldername.'/'.$file, $this->contents($file));
                }
            }
        }
        $this->cd($oldlocation);
        
        // Copy any assets:
        $oldlocation = $this->pwd();
        $this->cd($item);
        foreach($this->ls(false) as $asset){
            //if($asset == 'assets_'.$foldername.'/'){      // Store each template/component assets in a separate folder (assets_templateOrComponentName)?
                $this->getFolder($this->pwd().'/'.$asset, $rootname.'/'.$foldername);
            //}
        }
        $this->cd($oldlocation);
        
        return $rootname;
    }
    private function getFolder($folderToGet, $whereToPutIt){
        // Find out what to name directory:
        $foldername = explode('/', rtrim($folderToGet, '/'));
        $foldername = end(array_values($foldername));
        
        // Create directory:
        mkdir($whereToPutIt.'/'.$foldername);
        
        // Copy everything:
        $oldlocation = $this->pwd();
        $this->cd($folderToGet);
        foreach($this->ls() as $item){
            if($this->isDir($item)){    // Copy folders
                $this->getFolder($item, $whereToPutIt.'/'.$foldername);
            }
            else{   // Copy files
                file_put_contents($whereToPutIt.'/'.$foldername.'/'.$item, $this->contents($item));
            }
        }
        $this->cd($oldlocation);
    }
    public function getDownload($item){
        $folder = $this->copyToServer($item);
        $zipname = explode('/', glob($folder.'/*')[0])[1];

        // Zip up the folder inside $folder:
        $rootPath = realpath($folder.'/'.$zipname);
        $zip = new ZipArchive();
        $zip->open($folder.'/'.$zipname.'.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootPath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($files as $name => $file)
        {
            if (!$file->isDir())
            {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($rootPath) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
        $zip->close();

        return $folder.'/'.$zipname.'.zip';
    }
    private function deleteDir($dirPath) {
        if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
            $dirPath .= '/';
        }
        $files = glob($dirPath . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                $this->deleteDir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dirPath);
    }
    public function clearDownloads($seconds){
        $date = new DateTime();
        $stamp = $date->getTimestamp();
        foreach (glob("download_*") as $filename) {
            if(intval(substr($filename, 9)+$seconds) < ($stamp)){
                $this->deleteDir($filename);
            }
        }
    }
    private function fixRelatives($text){
        // Find all relative URLs
        $patterns = ['/((?:src|href)\s*=\s*(["\']))(\s*(?!#|\?|\/|https:\/\/|http:\/\/|\/\/|www\.).+?\2)/i', '/(url\s*\(\s*(["\'])\s*)((?!#|\?|\/|https:\/\/|http:\/\/|\/\/|www\.).*?\s*\2\))/i'];
        
        // Calculate what to prepend
        $prepend = $this->pwd();
        $prepend = trim($prepend, '/');
        $prepend = $this->parentURL . $prepend . '/';

        // Run the regex
        $result = preg_replace($patterns, '${1}'.$prepend.'${3}' , $text);

        return $result;
    }
    public function fixedcontents($path){
        return $this->fixRelatives($this->contents($path));
    }
    public function findselectors($text){
        $classes = array();
        $ids = array();
        $tags = array();

        preg_match_all('/class=[\'"](.*?)[\'"]/is',$text, $current);
        foreach($current[1] as $string){
            $each = preg_split('/\s+/', $string);
            $classes = array_merge($classes, $each);
        }
        $classes = array_unique($classes);
        preg_match_all('/id=[\'"](.*?)[\'"]/is',$text, $current);
        foreach($current[1] as $string){
            $string = preg_split('/\s+/', $string);
            $ids = array_merge($ids, $string);
        }
        $ids = array_unique($ids);
        preg_match_all('/<(\w+?)[\s>]/is',$text, $current);
        $tags = $current[1];
        $tags = array_unique($tags);
        return array('classes' => $classes, 'ids' => $ids, 'tags' => $tags);
    }
    private $mainCSS = 0;
    public function getcss(){
        if($this->mainCSS === 0){
            $text = '';
            $old = $this->pwd();
            $this->cd('/');
            foreach($this->ls() as $file){
                if(!$this->isDir($file)){
                    if(substr($file, -4) == '.css'){
                        $text .= $this->contents($file);
                    }
                }
            }
            $this->cd($old);
            $this->mainCSS = $text;
        }
        return $this->mainCSS;
    }
    public function filtercss($type, $name){
        $text = $this->getcss();
        if($type == 'class'){
            preg_match_all('/\.'.$name.'.*?{.*?}/is', $text, $results);
            return $results[0];
        }
        if($type == 'id'){
            preg_match_all('/#'.$name.'.*?{.*?}/is', $text, $results);
            return $results[0];
        }
        if($type == 'tag'){
            preg_match_all('/\b'.$name.'.*?{.*?}/is', $text, $results);
            return $results[0];
        }
    }
    public function findassets($text){   // Searches given text for any references to current folder
        preg_match_all('/'.$this->currentDir().'\/(.*?)[\'"]/is', $text, $results);
        $assets = $results[1];

        // Find any .psd's in the folder.
        foreach($results[1] as $asset){
            $temp = explode('/', $asset);
            $temp = end(array_values($temp));
            $temp = explode('.', $temp);
            array_pop($temp);
            $name = implode('.', $temp);
            foreach($this->ls(true, true) as $item){
                if(strtolower(substr($item, -4)) == '.psd' && strpos($item, $name) !== false){
                    $assets[] = $item;
                }
            }
        }
        return $assets;
    }
}
?>