<?php
    session_start();
    require_once('BitBucketRepo.php');
?>
<!DOCTYPE html>
<html>

<head>
    <title>NYU Atomic Styleguide</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="style.css">
    <link rel="stylesheet" type="text/css" href="demoClass.css">
    <?php
        // Start repo:
        if(isset($_SESSION['repo'])){
            $repo = $_SESSION['repo'];
        }
        else{
            $repo = new BitBucketRepo('https://bitbucket.org/api/1.0/repositories/mricotta/nyu/raw/master/');
        }

        // Output CSS inline:
        ?><style><?= $repo->getcss() ?></style><?php

        // Traverse the repo to proper location:
        if(isset($_GET['path']) && trim($_GET['path']) != ''){
            $path = urldecode($_GET['path']);
            $repo->cd($path);
            $singleFile = !(trim($repo->pwd(), '/') == trim($path, '/'));
        }
        else{
            $repo->cd('/');
            $singleFile = false;
        }

        // Start the download function if appropriate:
        if(isset($_GET['download']) && isset($_POST['downloadpath']) && $_GET['download'] == "TRUE"){
            $file = $repo->getdownload($_POST['downloadpath']);
            ?><script>window.open('<?= $file ?>');</script><?php
            $repo->clearDownloads(100);
        }
    ?>
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
                    <?php

                        // Display all directories in bitbucket root:
                        $current = $repo->pwd();
                        $repo->cd('/');
                        foreach($repo->ls(false) as $dir): ?>
                            <li class="dropdown">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><?= ucfirst(rtrim($dir, '/')) ?><span class="caret"></span></a>
                                <ul class="dropdown-menu">
                                    <?php 

                                        // Display contents of each directory:
                                        $repo->cd($dir);
                                        if((strpos($repo->pwd(), '/components') === 0)){    //If we're in the components root dir.
                                            foreach($repo->ls(false) as $item):       //Shows only directories
                                            //foreach($repo->ls() as $item):              //Shows everything
                                                $output = str_replace('.html', '', ucwords(str_replace('_', ' ', trim($item, '/'))));
                                                ?><li><a href="?path=<?= urlencode($repo->pwd().'/'.$item) ?>"><?= $output ?></a></li><?php
                                            endforeach;
                                        }
                                        elseif((strpos($repo->pwd(), '/templates') === 0)){ //If we're in the templates root dir.
                                            foreach($repo->ls() as $item):
                                                if(!$repo->isDir($item)){                 //Shows only files
                                                    $output = str_replace('.html', '', ucwords(str_replace('_', ' ', trim($item, '/'))));
                                                    ?><li><a href="?path=<?= urlencode($repo->pwd().'/'.$item) ?>"><?= $output ?></a></li><?php
                                                }
                                            endforeach;                            
                                        }
                                        else{
                                            foreach($repo->ls() as $item):
                                                $output = str_replace('.html', '', ucwords(str_replace('_', ' ', trim($item, '/'))));
                                                ?><li><a href="?path=<?= urlencode($repo->pwd().'/'.$item) ?>"><?= $output ?></a></li><?php
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
                    <!--<?php

                        // Display all directories in the current repo location, if not root:
                        if($repo->pwd() != '/'){
                            foreach($repo->ls(false) as $dir): ?>
                                <li class="dropdown">
                                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><?= ucfirst(rtrim($dir, '/')) ?><span class="caret"></span></a>
                                    <ul class="dropdown-menu">
                                    <?php 

                                        // Display contents of directory:
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
                    ?>-->
                </ul>
                <ul class="nav navbar-nav navbar-right">
                    <?php

                        // Get current location human-readable string:
                        $output = str_replace('.html', '', ucwords(str_replace('/', ' > ', str_replace('_', ' ', trim((isset($path))?$path:'', '/')))));
                    ?>
                    <p class="navbar-text"><strong><?= $output ?></strong></p>
                    <!--<?php

                        // A 'Go Up' button:
                        $curlink = $repo->pwd();
                        $repo->cd('..');
                        ?><li><a href="?path=<?= $repo->currentDir() ?>">Go Up <span class="glyphicon glyphicon-chevron-up" aria-hidden="true"></span></a></li><?php
                        $repo->cd($curlink);
                    ?>-->
                </ul>
            </div>
        </div>
    </nav>
    <div id="content" class="demo_class">
        <?php
            if(!$singleFile){   // If the URL does not specify a single file in the repo
                if(strpos($repo->pwd(), '/components') === 0){  // If in components root dir
                    $counter = 0;

                    // Display all items that are not directories:
                    foreach($repo->ls() as $item){
                        if(!$repo->isDir($item)){
                            ++$counter;
                            echo '<div class="singleElement">'; ?>
                                <div class="options">
                                    <?php
                                        $output = substr($item, 0, -5);     // Takes out the .html
                                        $output = ucwords(str_replace("_", ' ', $output));
                                    ?>
                                    <h4><?= $output ?></h4>
                                    <form action="<?= "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]&download=TRUE" ?>" method="POST">
                                        <input type="text" name="downloadpath" style="display:none;" value="<?= $repo->pwd().'/'.$item ?>" />
                                        <input type="submit" class="btn" value="Download Files .zip"></input>
                                    </form>
                                    <button class="btn" type="button" data-toggle="collapse" data-target="#html<?= $counter ?>" aria-expanded="false" aria-controls="html<?= $counter ?>">
                                        See the HTML
                                    </button>
                                    <button class="btn" type="button" data-toggle="collapse" data-target="#css<?= $counter ?>" aria-expanded="false" aria-controls="css<?= $counter ?>">
                                        See the CSS
                                    </button>
                                    <button class="btn" type="button" data-toggle="collapse" data-target="#assets<?= $counter ?>" aria-expanded="false" aria-controls="assets<?= $counter ?>">
                                        Download Individual Assets
                                    </button>
                                </div>
                                <div class="element">
                                    <div class="import"><?php echo $repo->fixedcontents($item); ?></div>
                                    <div class="collapse" id="html<?= $counter ?>">
                                        <div class="well">
                                            <h3>HTML:</h3>
                                            <pre><?= htmlspecialchars($repo->contents($item)) ?></pre>
                                            <?php 
                                                $html=$repo->contents($item);   // Used later to show assets only applicable to this HTML
                                            ?>
                                        </div>
                                    </div>
                                    <div class="collapse" id="css<?= $counter ?>">
                                        <div class="well">
                                            <h3>CSS:</h3>
                                            <?php 
                                                $css = '';  // Used later to show assets only applicable to this CSS
                                                $tags = $repo->findselectors($repo->contents($item));
                                                echo "<pre>";
                                                    foreach($tags['classes'] as $class){
                                                        foreach($repo->filtercss('class', $class) as $section){
                                                            echo $section;
                                                            echo "\n";
                                                            $css .= $section;
                                                        }
                                                    }
                                                    foreach($tags['ids'] as $id){
                                                        foreach($repo->filtercss('id', $id) as $section){
                                                            echo $section;
                                                            echo "\n";
                                                            $css .= $section;
                                                        }
                                                    }
                                                    foreach($tags['tags'] as $tag){
                                                        foreach($repo->filtercss('tag', $tag) as $section){
                                                            echo $section;
                                                            echo "\n";
                                                            $css .= $section;
                                                        }
                                                    }
                                                echo "</pre>";
                                            ?>
                                        </div>
                                    </div>
                                    <div class="collapse" id="assets<?= $counter ?>">
                                        <div class="well">
                                            <h3>Assets:</h3>
                                            <?php
                                                // Output the root css and js files:
                                                $oldlocation = $repo->pwd();
                                                $repo->cd('/');
                                                foreach($repo->ls() as $file){
                                                    if(!$repo->isDir($file)){
                                                        if(substr($file, -3) == '.js' || substr($file, -4) == '.css'){
                                                            ?><a href="<?= $repo->link($file) ?>" download="<?= $file ?>"><?= $file ?></a><br /><?php
                                                        }
                                                    }
                                                }
                                                $repo->cd($oldlocation);

                                                // Output the file itself:
                                                ?><a href="<?= $repo->link($item) ?>" download="<?= $item ?>"><?= $item ?></a><br /><?php

                                                // Output any assets:
                                                foreach($repo->ls(false) as $folder){
                                                    $oldlocation = $repo->pwd();
                                                    $repo->cd($folder);
                                                    $assets = $repo->findassets($html.'|'.$css);
                                                    foreach($assets as $asset){
                                                        $output = explode('/', $asset);
                                                        $output = end(array_values($output));
                                                        ?><a href="<?= $repo->link($asset) ?>" download="<?= $asset ?>"><?= $output ?></a><br /><?php
                                                    }
                                                    $repo->cd($oldlocation);
                                                }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            echo '</div>';
                        }
                    }
                }
                elseif(strpos($repo->pwd(), '/templates') === 0){   // If in templatesroot dir.

                    // Note: User should never arrive here because templates menu
                        // only shows single templates files. $singleFile would be true.
                    echo "<pre>" . $repo->pwd() . ":<br>";
                    print_r($repo->ls());
                    echo "</pre>";
                }
                else{   // If in some other root dir.
                    echo "<pre>" . $repo->pwd() . ":<br>";
                    print_r($repo->ls());
                    echo "</pre>";
                }
            }
            else{   // If a single file is specified in the URL

                // Display the single item
                ?>
                <div class="options">
                    <?php
                        $output = ucwords(preg_replace('/.*\/(.+)\.html/i', '${1}', str_replace('_', ' ', $path)));
                    ?>
                    <h4><?= $output ?></h4>
                    <form action="<?= "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]&download=TRUE" ?>" method="POST">
                        <input type="text" name="downloadpath" style="display:none;" value="<?= $path ?>" />
                        <input type="submit" class="btn" value="Download .zip"></input>
                    </form>
                </div>
                <div class="element">
                    <?php echo $repo->fixedcontents($path); ?>
                </div>
                <?php
            }
        ?>
    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <?php

        // Output javascript inline:
        $oldlocationlink = $repo->pwd();
        $repo->cd('/');
        foreach($repo->ls() as $file){
            if(!$repo->isDir($file)){
                if(substr($file, -3) == '.js'){
                    ?><script><?= $repo->fixedcontents($file) ?></script><?php
                }
            }
        }
        $repo->cd($oldlocationlink);
    ?>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <script src="http://getbootstrap.com/assets/js/ie10-viewport-bug-workaround.js"></script>
</body>

</html>