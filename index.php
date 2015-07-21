<?php
    require_once('BitBucketRepo.php');
    session_start();
?>
<!DOCTYPE html>
<html>

<head>
    <title>Atomic Styleguide</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css">
    <link rel="stylesheet" type="text/css" href="style.css">
    <link rel="stylesheet" type="text/css" href="demoClass.css">
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.6/styles/default.min.css"><!-- No idea if licensing allows this. -->
    <link rel="stylesheet" href="https://highlightjs.org/static/styles/github.css"><!-- No idea if licensing allows this. -->
    <?php
        // Start repo:
        if(isset($_SESSION['repo'])){
            $repo = $_SESSION['repo'];
        }
        else{
            $repo = new BitBucketRepo('https://bitbucket.org/api/1.0/repositories/bluefountainmedia/nyu/raw/development/');
            $_SESSION['repo'] = $repo;
        }

        // Output CSS inline:
        ?><style><?= $repo->getcss(true) ?></style><?php

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

<?php if(isset($_GET['fullscreen']) && isset($_GET['path']) && $_GET['fullscreen'] == 'true') : ?>
    <body>
    <?= $repo->contents($_GET['path']) ?>
<?php else: ?>
    <body id="atomic-styleguide-body">
        <nav class="navbar navbar-default navbar-fixed-top">
            <div class="container">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                        <span class="sr-only">Toggle navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <a class="navbar-brand" href="?">Atomic Style Guide</a>
                </div>
                <div id="navbar" class="navbar-collapse collapse">
                    <ul class="nav navbar-nav">
                        <li id="atomic-styleguide-search-button" style="cursor:pointer;"><p class="navbar-text"><i class="fa fa-search"></i></p></li>
                        <li id="atomic-styleguide-search-form" style="width:0; height:0; overflow:hidden;">
                            <form class="navbar-form navbar-left" role="search" method="GET" action="index.php">
                            <div class="form-group">
                                <input type="text" name="search" class="form-control" placeholder="Search">
                            </div>
                            </form>
                        </li>
                        <?php

                            // Display all directories in bitbucket root:
                            $current = $repo->pwd();
                            $repo->cd('/');
                            foreach($repo->ls(false) as $dir): 
                                // Ignore everything that's not components or templates
                                if(strtolower($dir) == "components/" || strtolower($dir) == "templates/"): ?>
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
                                endif;
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
            <?php if(isset($_GET['search'])): ?>
                <?php 
                    $search = htmlspecialchars($_GET['search']);
                ?>
                <div class="singleElement">
                    <h5>Closest things to '<?=$search?>' we could find:</h5>
                </div>
                <?php
                    function cmp($a, $b){
                        if($a == $b){
                            return 0;
                        }
                        similar_text(end(explode('/', $a)), $_GET['search'], $vala);
                        similar_text(end(explode('/', $b)), $_GET['search'], $valb);
                        if($vala >= $valb){
                            return -1;
                        }
                        else{
                            return 1;
                        }
                    }
                    $old = $repo->pwd();
                    $repo->cd('/');
                    $files = array();
                    foreach($repo->ls(true, true) as $path){
                        if(explode('/', $path)[0] == 'components' || explode('/', $path)[0] == 'templates'){
                            $files[] = $path;
                        }
                    }
                    usort($files, "cmp");
                    $ctr = 0;
                    foreach($files as $result){
                        if($ctr >= 5){
                            break;
                        }
                        ++$ctr;
                        ?>
                            <div class="singleElement">
                                <?php $output = str_replace('.html', '', ucwords(str_replace('/', ' > ', str_replace('_', ' ', trim((isset($result))?$result:'', '/'))))); ?>
                                <a href="?path=<?=$result?>"><?=$output?></a>
                            </div>
                        <?php
                    }
                    $repo->cd($old);
                ?>
            <?php else: ?>
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
                                            <a href="?path=<?=$repo->pwd().'/'.$item?>&fullscreen=true" target="_blank" class="btn" style="color:inherit;" id="atomic-styleguide-fullscreen">
                                                <i class="fa fa-arrows-alt fa-fw"></i> Fullscreen
                                            </a>
                                            <button class="btn" type="button" data-toggle="collapse" data-target="#html<?= $counter ?>" aria-expanded="false" aria-controls="html<?= $counter ?>">
                                                <i class="fa fa-html5 fa-fw"></i> See the HTML
                                            </button>
                                            <button class="btn" type="button" data-toggle="collapse" data-target="#css<?= $counter ?>" aria-expanded="false" aria-controls="css<?= $counter ?>">
                                                <i class="fa fa-css3 fa-fw"></i> See the CSS
                                            </button>
                                            <button class="btn" type="button" data-toggle="collapse" data-target="#assets<?= $counter ?>" aria-expanded="false" aria-controls="assets<?= $counter ?>">
                                                <i class="fa fa-download fa-fw"></i> Download Files
                                            </button>
                                        </div>
                                        <div class="element">
                                            <div class="import"><?php echo $repo->fixedcontents($item); ?></div>
                                            <div class="collapse" id="html<?= $counter ?>">
                                                <div class="well">
                                                    <h5>HTML:</h5>
                                                    <pre><code class='html'><?= htmlspecialchars($repo->contents($item)) ?></code></pre>
                                                    <?php 
                                                        $html=$repo->contents($item);   // Used later to show assets only applicable to this HTML
                                                    ?>
                                                </div>
                                            </div>
                                            <div class="collapse" id="css<?= $counter ?>">
                                                <div class="well">
                                                    <h5>CSS:</h5>
                                                    <?php 
                                                        $css = '';  // Used later to show assets only applicable to this CSS
                                                        $tags = $repo->findselectors($repo->contents($item));
                                                        echo "<pre><code class='css'>";
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
                                                        echo "</code></pre>";
                                                    ?>
                                                </div>
                                            </div>
                                            <div class="collapse" id="assets<?= $counter ?>">
                                                <div class="well">
                                                    <h5>Assets:</h5>
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
                                                        $oldlocation = $repo->pwd();
                                                        $repo->cd('/');
                                                        $assets = $repo->findassets($html.'|'.$css);
                                                        foreach($assets as $asset){
                                                            $output = explode('/', $asset);
                                                            $output = end(array_values($output));
                                                            ?><a href="<?= $repo->link($asset) ?>" download="<?= $asset ?>"><?= $output ?></a><br /><?php
                                                        }
                                                        $repo->cd($oldlocation);
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
                        elseif($repo->pwd() == '/'){   // If at document root.
                            ?>
                                <div id="homeContent">
                                    <h2>Welcome...</h2>
                                    <h4>...to the Atomic Style Guide! :)</h4><br />
                                    <h5>Here's what we do:</h5>
                                    <p>
                                        In atomic designs (designs that are broken down into individual components/templates), 
                                        we are the style guide. We will display each component, show its HTML 
                                        and CSS code, and allow it (and each or all of its dependencies) to be downloaded at the click of
                                        a button.<br /><br />
                                        Each element is directly accessed from where it is stored, so there is no updating
                                        or CMS to worry about. We will always display the latest and greatest version of every element.
                                    </p><br /><br />
                                    <h5>Here's how to use us:</h5>
                                    <p>
                                        Just create a publicly viewable BitBucket repository (support for private ones is coming soon), store your design
                                        elements in there, and we'll take care of the rest. <br /><br />
                                        Just follow three rules:<br />
                                        <ol>
                                            <li>
                                                Store master .css and .js files in the root directory. If you want to have multiple .css files, you can use @import statements.
                                                <ul>
                                                    <li>
                                                        Note that .css files in subdirectories are still loaded by the framework (in order to display any CSS that may be hidden behind @import statements). 
                                                    </li>
                                                    </li>
                                                        However the root directory .css files (and any files they directly reference) are the only ones that are accessible via download buttons.
                                                    </li>
                                                </ul>
                                            </li>
                                            <li>Store your component .html's like this: components/component-subgroup/individual-component.html</li> 
                                            <li>Store your template .html's like this: templates/individual-template.html</li>
                                        </ol>
                                    </p>
                                </div>
                            <?php                    
                        }
                        else{   // If in some other root dir.
                            echo "<pre>" . $repo->pwd() . ":<br>";
                            print_r($repo->ls(true, true));
                            echo "</pre>";
                        }
                    }
                    else{   // If a single file is specified in the URL

                        // Display the single item
                        ?>
                        <div class="singleElement">
                            <div class="options">
                                <?php
                                    $output = ucwords(preg_replace('/.*\/(.+)\.html/i', '${1}', str_replace('_', ' ', $path)));
                                ?>
                                <h4><?= $output ?></h4>
                                <form action="<?= "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]&download=TRUE" ?>" method="POST">
                                    <input type="text" name="downloadpath" style="display:none;" value="<?= $path ?>" />
                                    <input type="submit" class="btn" value="Download Files .zip"></input>
                                </form>
                                <a href="?path=<?=$path?>&fullscreen=true" target="_blank" class="btn" style="color:inherit;" id="atomic-styleguide-fullscreen">
                                    <i class="fa fa-arrows-alt fa-fw"></i> Fullscreen
                                </a>
                                <button class="btn" type="button" data-toggle="collapse" data-target="#html" aria-expanded="false" aria-controls="html">
                                    <i class="fa fa-html5 fa-fw"></i> See the HTML
                                </button>
                                <button class="btn" type="button" data-toggle="collapse" data-target="#css" aria-expanded="false" aria-controls="css">
                                    <i class="fa fa-css3 fa-fw"></i> See the CSS
                                </button>
                                <button class="btn" type="button" data-toggle="collapse" data-target="#assets" aria-expanded="false" aria-controls="assets">
                                    <i class="fa fa-download fa-fw"></i> Download Files
                                </button>
                            </div>
                            <div class="element">
                                <div class="collapse" id="html">
                                    <div class="well">
                                        <h5>HTML:</h5>
                                        <pre><code class='html'><?= htmlspecialchars($repo->contents($path)) ?></code></pre>
                                        <?php 
                                            $html=$repo->contents($path);   // Used later to show assets only applicable to this HTML
                                        ?>
                                    </div>
                                </div>
                                <div class="collapse" id="css">
                                    <div class="well">
                                        <h5>CSS:</h5>
                                        <?php 
                                            $css = '';  // Used later to show assets only applicable to this CSS
                                            $tags = $repo->findselectors($repo->contents($path));
                                            echo "<pre><code class='css'>";
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
                                            echo "</code></pre>";
                                        ?>
                                    </div>
                                </div>
                                <div class="collapse" id="assets">
                                    <div class="well">
                                        <h5>Assets:</h5>
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
                                            ?><a href="<?= $repo->link($path) ?>" download="<?= $path ?>"><?= $path ?></a><br /><?php

                                            // Output any assets:
                                            $oldlocation = $repo->pwd();
                                            $repo->cd('/');
                                            $assets = $repo->findassets($html.'|'.$css);
                                            foreach($assets as $asset){
                                                $output = explode('/', $asset);
                                                $output = end(array_values($output));
                                                ?><a href="<?= $repo->link($asset) ?>" download="<?= $asset ?>"><?= $output ?></a><br /><?php
                                            }
                                            $repo->cd($oldlocation);
                                        ?>
                                    </div>
                                </div>
                                <?php echo $repo->fixedcontents($path); ?>
                            </div>
                        </div>
                        <?php
                    }
                ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>
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
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
        <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
        <script src="http://getbootstrap.com/assets/js/ie10-viewport-bug-workaround.js"></script>
        <script src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.6/highlight.min.js"></script><!-- No idea if licensing allows this. -->
        <script>
            hljs.initHighlightingOnLoad();
            var search_expanded = false;
            $('#atomic-styleguide-search-button').click(function(){
                $('#atomic-styleguide-search-form').animate({ width: (search_expanded)?'0':'225px', height: (search_expanded)?'0':'51px' }, 500, function(){
                    search_expanded = !search_expanded;
                });
                $('#atomic-styleguide-search-button').css('background-color', (search_expanded)?'inherit':'#CCCCCC');
            });
        </script>
    </body>

</html>