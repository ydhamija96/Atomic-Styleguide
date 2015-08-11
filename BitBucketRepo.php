<?php
class BitBucketRepo{
    private $directoryListing = array();
    private $parentURL;
    private $ourUrl;
    private $currentLoc = array();
    private $mainCSS = 0;
    private $fixedCSS = 0;
    private $allMainCSS = 0;
    private $allFixedCSS = 0;
    private $nonce;
    function __construct($url, $ourSubUrl=''){
        $this->parentURL = $url;
        $this->ourUrl = $ourSubUrl;
        $this->directoryListing = $this->getAllContents($this->parentURL, $this->directoryListing);
        $this->mainCSS = 0;
        $this->fixedCSS = 0;
        $this->allMainCSS = 0;
        $this->allFixedCSS = 0;
        $this->nonce = hash('sha512', rand());
        $_SESSION['nonce'] = $this->nonce;
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
        $link = rtrim($this->parentURL, '/').'/';
        $old = $this->pwd();
        $this->cd($path);
        $link .= $this->pwd();
        if(end(explode('/', $path)) != end(explode('/', $link))){
            $link = rtrim($link, '/').'/'.end(explode('/', $path));
        }
        $this->cd($old);
        return $link;
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
        $this->file_force_contents($rootname.'/'.$foldername.'/'.$item, $this->contents($item));

        // Put root .css and .js in it:
        $oldlocation = $this->pwd();
        $this->cd('/');
        foreach($this->ls() as $file){
            if(!$this->isDir($file)){
                if(substr($file, -4) == '.css'){
                    $this->file_force_contents($rootname.'/'.$foldername.'/'.$file, $this->contents($file));
                }
            }
        }
        $this->cd($oldlocation);

        // Copy any assets:
        $oldlocation = $this->pwd();
        $this->cd($item);
        $html = $this->contents($item);
        $css = $this->relevantCSS($item);
        $old = $this->pwd();
        $this->cd('/');
        $assets = $this->findassets($this->contents('/atomicstyleguide-autoload.html').'|'.$html.'|'.$css);
        foreach($assets as $asset){
            $this->file_force_contents($rootname.'/'.$foldername.'/'.$asset, $this->contents($asset));
        }
        $this->cd($old);
        $this->cd($oldlocation);

        return $rootname;
    }
    private function file_force_contents($dir, $contents){
        $parts = explode('/', $dir);
        $file = array_pop($parts);
        $dir = '';
        foreach($parts as $part){
            $dir .= $part.'/';
            if(!is_dir($dir)){
                mkdir($dir);
            }
        }
        file_put_contents("$dir/$file", $contents);
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
    public function remove_relative_css_js_links($text){
        $our = preg_quote(trim($this->ourUrl, '/'), '/');
        $our = '\/*?'.$our.'\/*?';
        $patterns = [
            '/<\s*?link[^<>]*?href\s*?=\s*?[\'"]\s*?'.$our.'downloadnshow\.php\?url=ssc\.[^<>]*?[\'"][^<>]*?>/i',
            '/<\s*?script[^<>]*?src\s*?=\s*?[\'"]\s*?'.$our.'downloadnshow\.php\?url=sj\.[^<>]*?[\'"][^<>]*?>.*?<\/script>/i'
        ];
        $text = preg_replace($patterns, '', $text);
        return $text;
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
    public function fixRelatives($text){
        // Find all relative URLs
        $patterns = [
            '/\b\s*((?:src|href)\s*=\s*(["\']))(\s*((?!#|\?|https:\/\/|http:\/\/|\/\/|www\.)\s*?[^+\'"]+?.*?[^+\.]+?)\2)/i',
            '/\b\s*(url\s*\(\s*(["\']?)\s*)(((?!#|\?|https:\/\/|http:\/\/|\/\/|www\.)\s*?[^+\'"]+?.*?[^+\.]+?)\s*\2\))/i',
            '/@(import\s*(["\']))(\s*((?!#|\?|https:\/\/|http:\/\/|\/\/|www\.)\s*?[^+\'"]+?.*?[^+\.]+?)\2)/i'
        ];

        // Find all matches
        $matches = array();
        foreach($patterns as $pattern){
            preg_match_all($pattern, $text, $result);
            $matches = array_merge($matches, $result[4]);
        }

        // Download each match, and replace matches with new downloaded links
        // $date = new DateTime();
        // $stamp = session_id();
        // $count = 0;
        // foreach($matches as $match){

        //     // Find extension
        //     preg_match('/\.[^.]+?$/', $match, $extension);
        //     $extension = $extension[0];

        //     // Download the file
        //     $filename = 'resources/'.$stamp.'/res_'.$count++.'_'.$extension;
        //     $this->file_force_contents($filename, $this->contents($match));

        //     // Replace URL
        //     $text = str_replace($match, $filename, $text);
        // }
        //$this->cleanResources();

        // Replace matches with a special download+show link
        foreach($matches as $match){
            $text = str_replace($match, $this->ourUrl.'/downloadnshow.php?url='.strrev($this->link($match)), $text);   // Reversing string so that similar matches don't trigger multiple replacements
        }

        return $text;
    }
    private function cleanResources(){
        $session_path = ini_get('session.save_path');
        $session_files_dir = 'resources/';
        if ($handle = opendir($session_files_dir)) {
            while (false !== ($file = readdir($handle))) {
                if ($file != '.' && $file != '..') {
                    if (  file_exists("$session_path/sess_$file")  ) {
                        // session is still alive
                    } else {
                        $this->deleteDir($session_files_dir.$file);
                    }
                }
            }
            closedir($handle);
        }
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
            $string = trim($string);
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
    public function fixCSS($text){
        $old = $this->pwd();
        $this->cd('/');
        $text = preg_replace('/\/\*.*?\*\//','', $text);
        $text = $this->fixRelatives($text);
        $text = "p{} ".$text;
        $text = preg_replace('/([},][^@\w\.#]*?)(?!\.import)([\w\.#])/is', '${1} .import ${2}', $text);
        $text = preg_replace('/(@[^{]*?{[^@\w\.#]*?)(?!\.import)([\w\.#])/is', '${1} .import ${2}', $text);
        //// Removed because it broke the previous regex. I don't remember why it was here.
        //$text = preg_replace('/(}[^{]*?{[^}]*?)\.import([^}]*)(?=})/is', '${1} ${2}', $text);
        while(preg_match('/\.import([^{]*?\;)/is', $text)){
            $text = preg_replace('/\.import([^{]*?\;)/is', '${1}', $text);
        }
        $this->cd($old);
        return substr($text, 4);
    }
    public function getcss($fix = false, $all = false){
        if($all){
            if($fix){
                if($this->allFixedCSS == 0){
                    $text = '';
                    $text .= $this->fixCSS($this->getcss(false, $all));
                    $this->allFixedCSS = $text;
                }
                return $this->allFixedCSS;
            }
            else{
                if($this->allMainCSS == 0){
                    $text = '';
                    $old = $this->pwd();
                    $this->cd('/');
                    foreach($this->ls(true, true) as $file){
                        if(!$this->isDir($file)){
                            if(substr($file, -4) == '.css'){
                                $text .= $this->contents($file);
                            }
                        }
                    }
                    $this->cd($old);
                    $this->allMainCSS = $text;
                }
                return $this->allMainCSS;
            }
        }
        else{
            if($fix){
                if($this->fixedCSS == 0){
                    $text = '';
                    $text .= $this->fixCSS($this->getcss(false));
                    $this->fixedCSS = $text;
                }
                return $this->fixedCSS;
            }
            else{
                if($this->mainCSS == 0){
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
        }
    }
    private function parse_css_media_queries($css){
        $mediaBlocks = array();
        $start = 0;
        while(($start = strpos($css, "@media", $start)) !== false){
            $s = array();
            $i = strpos($css, "{", $start);
            if ($i !== false){
                array_push($s, $css[$i]);
                ++$i;
                while (!empty($s)){
                    if ($css[$i] == "{"){
                        array_push($s, "{");
                    }
                    elseif ($css[$i] == "}"){
                        array_pop($s);
                    }
                    ++$i;
                }
                $mediaBlocks[] = substr($css, $start, ($i + 1) - $start);
                $start = $i;
            }
        }
        return $mediaBlocks;
    }
    private function filtercss($type, $names){
        $text = preg_replace('/\/\*.*?\*\//','', $this->getcss(false, true));
        $mediablocks = $this->parse_css_media_queries($text);
        $namesregex = '(?:';
        if(!empty($names)){
            foreach($names as $name){
                if(trim($name) != ''){
                    $namesregex .= preg_quote($name) . '|';
                }
            }
            $namesregex = rtrim($namesregex, '|');
            $namesregex .= ')';
        }
        else{
            return $text;
        }
        if($type == 'class'){
            preg_match_all('/(\.'.$namesregex.'\b.*?)(?=})/is', $text, $results);
        }
        if($type == 'id'){
            preg_match_all('/(#'.$namesregex.'\b.*?)(?=})/is', $text, $results);
        }
        if($type == 'tag'){
            preg_match_all('/[,}]\s*?('.$namesregex.'\b(?!\.).*?)(?=})/is', $text, $results);
        }
        foreach($results[1] as &$result){
            $prepend = '';
            $postpend = '}';
            foreach($mediablocks as $mediablock){
                if(strpos($mediablock, $result) !== false){
                    preg_match('/@media.*?{/i', $mediablock, $prepends);
                    $prepend .= $prepends[0] . PHP_EOL . '  ';
                    $postpend .= PHP_EOL.'}';
                    break;
                }
            }
            $result = preg_replace('/,.*?{/is',' {', $result);
            $result = $prepend.$result.$postpend;
        }
        return $results[1];
    }
    public function relevantCSS($path){
        $result = '';
        $classblocks = array();
        $idblocks = array();
        $tagblocks = array();
        $tags = $this->findselectors($this->contents($path));
        if(!empty($tags['classes'])){
            foreach($this->filtercss('class', $tags['classes']) as $section){
                $classblocks[] = $section."\n";
            }
        }
        if(!empty($tags['ids'])){
            foreach($this->filtercss('id', $tags['ids']) as $section){
                $idblocks[] = $section."\n";
            }
        }
        if(!empty($tags['tags'])){
            foreach($this->filtercss('tag', $tags['tags']) as $section){
                $tagblocks[] = $section."\n";
            }
        }
        $classblocks = array_unique($classblocks);
        $idblocks = array_unique($idblocks);
        $tagblocks = array_unique($tagblocks);
        foreach($classblocks as $block){
            $result .= $block;
        }
        foreach($idblocks as $block){
            $result .= $block;
        }
        foreach($tagblocks as $block){
            $result .= $block;
        }
        return $result;
    }
    private function findassets($text){
        $old = $this->pwd();
        $this->cd('/');
        $assets = array();
        $patterns = [
            '/\b\s*((?:src|href)\s*=\s*(["\']))(\s*((?!#|\?|https:\/\/|http:\/\/|\/\/|www\.)\s*?[^+\'"]+?.*?[^+\.]+?)\2)/i',
            '/\b\s*(url\s*\(\s*(["\']?)\s*)(((?!#|\?|https:\/\/|http:\/\/|\/\/|www\.)\s*?[^+\'"]+?.*?[^+\.]+?)\s*\2\))/i',
            '/@(import\s*(["\']))(\s*((?!#|\?|https:\/\/|http:\/\/|\/\/|www\.)\s*?[^+\'"]+?.*?[^+\.]+?)\2)/i'
        ];
        $matches = array();
        foreach($patterns as $pattern){
            preg_match_all($pattern, $text, $result);
            $matches = array_merge($matches, $result[4]);
        }
        $matches = array_unique($matches);

        foreach($matches as $asset){
            foreach($this->ls(true, true) as $item){
                if(strpos($item, trim($asset, "&quot;")) !== false){
                    $assets[] = $item;
                }
            }
        }
        $assets = array_unique($assets);

        $this->cd($old);
        return $assets;
    }
    public function relevantDownloads($item, $givenhtml = null, $givencss = null){
        $result = '';

        // Output the root css and js files:
        $oldlocation = $this->pwd();
        $this->cd('/');
        foreach($this->ls() as $file){
            if(!$this->isDir($file)){
                if(substr($file, -4) == '.css'){
                    $result .= '<a href="'.$this->link($file).'" download="'.$file.'">'.$file.'</a><br />';
                }
            }
        }
        $this->cd($oldlocation);

        // Output the file itself:
        $result .= '<a href="'.$this->link($item).'" download="'.$item.'">'.$item.'</a><br />';

        // Output any assets:
        if($givenhtml){
            $html = $givenhtml;
        }
        else{
            $html = $this->contents($item);
        }
        if($givencss){
            $css = $givencss;
        }
        else{
            $css = $this->relevantCSS($item);
        }
        $oldlocation = $this->pwd();
        $this->cd('/');
        $assets = $this->findassets($this->contents('/atomicstyleguide-autoload.html').'|'.$html.'|'.$css);
        foreach($assets as $asset){
            $output = explode('/', $asset);
            $output = end(array_values($output));
            $result .= '<a href="'.$this->link($asset).'" download="'.$asset.'">'.$output.'</a><br />';
        }
        $this->cd($oldlocation);

        return $result;
    }
}
?>
