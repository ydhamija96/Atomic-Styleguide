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
            $repo = new BitBucketRepo('https://bitbucket.org/api/1.0/repositories/bluefountainmedia/nyu/raw/development/', '');
            $_SESSION['repo'] = $repo;
        }

        // Output CSS inline:
        if(isset($_GET['fullscreen']) && $_GET['fullscreen'] == 'true'){
            ?><style><?= $repo->getcss(true) ?></style><?php
        }
        else{
            ?><style><?= str_replace('position:fixed', 'position:relative', $repo->getcss(true)) ?></style><?php
        }

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
    <script>
        var paths = [];
        var loadingText = [
            "Please wait, 640K ought to be enough for anybody.",
            "Please wait, the bits are breeding.",
            "Please wait. At least you're not on hold.",
            "Please wait while the satellite moves into position.",
            "Please wait. The last time I tried this the monkey didn't survive. Let's hope it works better this time.",
            "Loading humorous message. Please wait."
        ];
    </script>
</head>
<?php if(isset($_GET['fullscreen']) && isset($_GET['path']) && $_GET['fullscreen'] == 'true') : ?>
    <body class="import">
    <?= $repo->remove_relative_css_js_links($repo->fixedcontents($_GET['path'])) ?>
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
                    <a class="navbar-brand atomic-styleguide-brand" href="?"><i class="atomicstyleguide-navbar-logo atomicstyleguide-logo fa-flip-vertical fa fa-simplybuilt"></i>&nbsp;  Atomic Style Guide</a>
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
                                                        // Only show folders that have at least one .html file in them
                                                        $old = $repo->pwd();
                                                        $repo->cd($item);
                                                        foreach($repo->ls() as $file){
                                                            if(end(explode('.', $file)) == 'html'){
                                                                $output = str_replace('.html', '', ucwords(str_replace('_', ' ', trim($item, '/'))));
                                                                ?><li><a href="?path=<?= urlencode($old.'/'.$item) ?>"><?= $output ?></a></li><?php   
                                                                break;                                                             
                                                            }
                                                        }
                                                        $repo->cd($old);
                                                    endforeach;
                                                }
                                                elseif((strpos($repo->pwd(), '/templates') === 0)){ //If we're in the templates root dir.
                                                    foreach($repo->ls() as $item):
                                                        if(!$repo->isDir($item) && end(explode('.', $item)) == 'html'){                 //Shows only .html files
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
                    <h3>Closest things to '<?=$search?>' we could find:</h3>
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
                        if((explode('/', $path)[0] == 'components' || explode('/', $path)[0] == 'templates') && end(explode('.', $path)) == 'html'){
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

                            // Display all .html files:
                            foreach($repo->ls() as $item){
                                if(!$repo->isDir($item) && end(explode('.', $item)) == 'html'){
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
                                            <script>
                                                paths.push("<?= $repo->pwd().'/'.$item ?>");
                                            </script>
                                            <div class="collapse" id="html<?= $counter ?>">
                                                <div class="well">
                                                    <h5>HTML:</h5>
                                                    <pre><code id="htmlCode<?= $counter ?>" class='html'><i class="fa fa-cog fa-3x fa-spin"></i> <script>document.write(loadingText[Math.floor(Math.random()*loadingText.length)]);</script></code></pre>
                                                </div>
                                            </div>
                                            <div class="collapse" id="css<?= $counter ?>">
                                                <div class="well">
                                                    <h5>CSS:</h5>
                                                    <pre><code id='cssCode<?= $counter ?>' class='css'><i class="fa fa-cog fa-3x fa-spin"></i> <script>document.write(loadingText[Math.floor(Math.random()*loadingText.length)]);</script></code></pre>
                                                </div>
                                            </div>
                                            <div class="collapse" id="assets<?= $counter ?>">
                                                <div class="well">
                                                    <h5>Assets:</h5>
                                                    <p id="assetsCode<?= $counter ?>"><i class="fa fa-cog fa-3x fa-spin"></i> <script>document.write(loadingText[Math.floor(Math.random()*loadingText.length)]);</script></p>
                                                </div>
                                            </div>
                                            <div class="import"><?php echo $repo->remove_relative_css_js_links($repo->fixedcontents($item)); ?></div>
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
                                    <i style="margin-top:10px; border-color:#996699;" id="logo" class="atomicstyleguide-logo fa-flip-vertical pull-left fa fa-simplybuilt fa-5x fa-border"></i> 
                                    <h2>Welcome to the...</h2>
                                    <h1>Atomic Style Guide!</h1><br />
                                    <h3>What it does:</h3>
                                    <p>
                                        In atomic designs (designs that are broken down into individual components/templates), 
                                        this is the style guide. It will display each component, show its HTML 
                                        and CSS code, and allow it (and each of its dependencies) to be downloaded at the click of
                                        a button.<br /><br />
                                        Each element is directly accessed from where it is stored, so there is no updating
                                        or CMS to worry about. It will always display the latest and greatest version of every element.
                                    </p><br /><br />
                                    <h3>How to use it:</h3>
                                    <p>
                                        Just create a publicly viewable BitBucket repository (support for private ones is coming soon) and store your design
                                        elements in there. <br />
                                        <ul>
                                            <li>
                                                <strong>HTML:</strong>
                                                <ul>
                                                    <li>Store your components' HTML files like this: <span>/components/[component-subgroup]/[individual-component].html</span>.</li> 
                                                    <li>Store your templates' HTML files like this: <span>/templates/[individual-template].html</span>.</li>
                                                </ul>
                                            </li>
                                            <li>
                                                <strong>CSS:</strong>
                                                <ul>
                                                    <li>
                                                        CSS files are only loaded from the root.
                                                        <ul>
                                                            <li>However, you can use <span>@import</span> statements to load other CSS files which may be in subdirectories.</li>
                                                        </ul>
                                                    </li>
                                                    <li>
                                                        If you'd like to have your CSS load in a specific order, you have two options:
                                                        <ul>
                                                            <li>Put it all in one file in the root.</li>
                                                            <li>Put a single CSS file in the root that imports other CSS files from subdirectories in the 
                                                            preferred order (via <span>@import</span>).</li>
                                                        </ul>
                                                    </li>
                                                    <li>
                                                        Do not leave a <span>&lt;link rel="stylesheet" type="text/css" href="[cssFile].css"&gt;</span> in your HTML code.
                                                    </li>
                                                </ul>
                                            </li>
                                            <li>
                                                <strong>JavaScript:</strong>
                                                <ul>
                                                    <li>
                                                        Javascript can be loaded through this file: <span>/atomicstyleguide-autoload.html</span>.
                                                        <ul>
                                                            <li>If it exists, it will be autoloaded by the styleguide.</li>
                                                            <li>Javascript can be included there inline.</li>
                                                            <li>You can also keep separate Javascript files and load them there (via <span>&lt;script src=[file].js&gt;</span>). This is the preferred way.</li>
                                                            <li>Note that this file is <strong>only</strong> loaded in fullscreen mode.</li>
                                                        </ul>
                                                    </li>
                                                </ul>
                                            </li>
                                        </ul>
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
                                <script>
                                    paths.push("<?= $path ?>");
                                </script>
                                <div class="collapse" id="html">
                                    <div class="well">
                                        <h5>HTML:</h5>
                                        <pre><code id='htmlCode1' class='html'><i class="fa fa-cog fa-3x fa-spin"></i> <script>document.write(loadingText[Math.floor(Math.random()*loadingText.length)]);</script></code></pre>
                                    </div>
                                </div>
                                <div class="collapse" id="css">
                                    <div class="well">
                                        <h5>CSS:</h5>
                                        <pre><code id='cssCode1' class='css'><i class="fa fa-cog fa-3x fa-spin"></i> <script>document.write(loadingText[Math.floor(Math.random()*loadingText.length)]);</script></code></pre>
                                    </div>
                                </div>
                                <div class="collapse" id="assets">
                                    <div class="well">
                                        <h5>Assets:</h5>
                                        <p id="assetsCode1"><i class="fa fa-cog fa-3x fa-spin"></i> <script>document.write(loadingText[Math.floor(Math.random()*loadingText.length)]);</script></p>
                                    </div>
                                </div>
                                <div class="import"><?php echo $repo->remove_relative_css_js_links($repo->fixedcontents($path)); ?></div>
                            </div>
                        </div>
                        <?php
                    }
                ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
        <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
        <script src="http://getbootstrap.com/assets/js/ie10-viewport-bug-workaround.js"></script>
        <script src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.6/highlight.min.js"></script><!-- No idea if licensing allows this. -->
        <script>
            var search_expanded = false;
            $('#atomic-styleguide-search-button').on("click",function(){
                $('#atomic-styleguide-search-form').animate({ width: (search_expanded)?'0':'225px', height: (search_expanded)?'0':'51px' }, 500, function(){
                    search_expanded = !search_expanded;
                });
                $('#atomic-styleguide-search-button').css('background-color', (search_expanded)?'inherit':'#CCCCCC');
            });
            $('.nav a').on("click",function(){
                window.stop();
            });
            $(".atomic-styleguide-brand").on({
                mouseenter: function () {
                    $(".atomicstyleguide-navbar-logo").addClass("fa-pulse");
                },
                mouseleave: function () {
                    $(".atomicstyleguide-navbar-logo").removeClass("fa-pulse");
                }
            });
        </script>
        <script>
            var available = [];
            $(function() {
                for(var i=1; i<=paths.length; ++i){
                    available.push('');
                    $.ajax({ url: 'functions.php',
                        data: {
                            action: 'relevantHTML',
                            input: i,
                            path: paths[i-1]
                        },
                        type: 'post',
                        success: function(output){
                            var returned = jQuery.parseJSON(output);
                            var dom = $('#htmlCode'+returned.Input);
                            dom.html(returned.Output);
                            dom.each(function(i, block) {
                                hljs.highlightBlock(block);
                            });
                            if(available[returned.Input] != ''){
                                $.ajax({ url: 'functions.php',
                                    data: {
                                        action: 'relevantAssets',
                                        input: returned.Input,
                                        css: available[returned.Input],
                                        html: returned.Output,
                                        path: paths[returned.Input-1]
                                    },
                                    type: 'post',
                                    success: function(output){
                                        var returned = jQuery.parseJSON(output);
                                        var dom = $('#assetsCode'+returned.Input);
                                        dom.html(returned.Output);
                                    }
                                });
                            }
                            else{
                                available[returned.Input] = returned.Output;
                            }
                        }
                    });
                    $.ajax({ url: 'functions.php',
                        data: {
                            action: 'relevantCSS',
                            input: i,
                            path: paths[i-1]
                        },
                        type: 'post',
                        success: function(output){
                            var returned = jQuery.parseJSON(output);
                            var dom = $('#cssCode'+returned.Input);
                            dom.html(returned.Output);
                            dom.each(function(i, block) {
                                hljs.highlightBlock(block);
                            });
                            if(available[returned.Input] != ''){
                                $.ajax({ url: 'functions.php',
                                    data: {
                                        action: 'relevantAssets',
                                        input: returned.Input,
                                        html: available[returned.Input],
                                        css: returned.Output,
                                        path: paths[returned.Input-1]
                                    },
                                    type: 'post',
                                    success: function(output){
                                        var returned = jQuery.parseJSON(output);
                                        var dom = $('#assetsCode'+returned.Input);
                                        dom.html(returned.Output);
                                    }
                                });
                            }
                            else{
                                available[returned.Input] = returned.Output;
                            }
                        }
                    });
                }
            });
        </script>
        <?php

            // Autoload the atomicstyleguide-autoload.html file:
            ?>
                <div style="position:absolute; padding:0; margin:0; border:none; height:0; width:0; overflow:hidden; display:none;">
                    <?php
                        $oldlocation = $repo->pwd();
                        $repo->cd('/');
                        if(isset($_GET['fullscreen']) && $_GET['fullscreen'] == 'true'){
                            echo $repo->fixedcontents('/atomicstyleguide-autoload.html');
                        }
                        $repo->cd($oldlocation);
                    ?>
                </div>
            <?php
        ?>
    </body>

</html>