<?php

$url = 'http://www.tronixweb.com/';

$maxPage = 20;
if(isset($argv[1])) {
	$maxPage = intval($argv[1]);
	if ($maxPage === 0) {
		$maxPage = 20;
	}
	echo "Max pages have been changed to $maxPage. \n";
}

$sleeptime = 1;
if(isset($argv[2])) {
	$sleeptime = intval($argv[2]);
	echo "Sleep has been changed to $sleeptime. \n";
}

// List of URLs to be crawled
$urlList = array(
    $url
);

// List of product images that we found on this site
$imageList = array();

$breadcrumbMap = array();

// Crawl HTML pages
for($page=0; $page<count($urlList) && $page<$maxPage; $page++) {
    // Throttle the scan so we don't overwhelm the remote server
	sleep($sleeptime);
	
	echo $urlList[$page], "\n\n";

    // Fetch the remote page if it exists
    $content = @file_get_contents($urlList[$page]);
    if (! $content) {
        continue;
    }

    // Print the page's title
    $pageTitle = '[ Untitled Page ]';
    if (preg_match('@<title>([^<]+)</title>@i', $content, $match) !=0) {
        $pageTitle = $match[1];
    }
    echo $pageTitle, "\n", str_repeat('-', strlen($pageTitle)), "\n";

    // Find and display any breadcrumbs
    if (preg_match('@<div[^>]+?id="breadcrumbs"[^>]*>([\s\S]+?)</div>@i', $content, $match) != 0) {
        // Separate a string into an array around the breadcrumb delimiters in $match[1] and put it into $breadcrumbSections
        $breadcrumbSections = explode('<span>&gt;</span>', $match[1]); 
        // Initialize $mapPointer as a reference to the base of $breadcrumbMap
        $mapPointer = &$breadcrumbMap;
        // Start a foreach loop where we break out each element as $section on each pass
        foreach($breadcrumbSections as $section) {
            // grabbing the breadcrumb link and name from the anchor tag 
            if (preg_match('@<a href="([^>"]+)">([^<]+)</a>@', $section, $breadcrumbMatch) == 1) {
                // Set $nothing to $breadcrumbMatch[0] (because it's not used), $crumbUrl to $breadcrumbMatch[1], and $crumbName to $breadcrumbMatch[2]
                list($nothing, $crumbUrl, $crumbName) = $breadcrumbMatch;
                // checking if $mapPointer -> children is empty
                if (empty($mapPointer['children'])) {
                    // if empty, initialize $mapPointer['children'] as an empty array
                    $mapPointer['children'] = array();
                }
                // checking if $mapPointer -> children -> $crumbUrl is empty
                if (empty($mapPointer['children'][$crumbUrl])) {
                    // setting the variables url, children and name
                    $mapPointer['children'][$crumbUrl] = array(
                        'url'       => $crumbUrl,
                        'name'      => $crumbName,
                        'children'  => array(),
                    );
                }
                // Update $mapPointer so that it moves one section further down the $breadcrumbMap structure
                $mapPointer = &$mapPointer['children'][$crumbUrl];
            }
        }
        // sanitizing $breadcrumbs by turning HTML entities back into characters after removing all tags inside the string
        $breadcrumb = html_entity_decode(strip_tags($match[1]));
        echo '>> ', preg_replace('@\s*>\s*@', ' > ', $breadcrumb), "\n";
    }

    // Check for any internal links
    if (preg_match_all('@<a\b[^>]*?href="(https?://[^/"]*?\btronixweb\.com/[^>"]+)"@i', $content, $matches) != 0) {
        $addedLinks = 0;

        foreach($matches[1] as $newUrl) {
            if (! in_array($newUrl, $urlList)) {
                $urlList[] = $newUrl;
                $addedLinks++;
            }
        }

        if($addedLinks) {
            echo $addedLinks, " new URLs added\n";
        }
    }

    // Look for any product images
    if (preg_match_all('@<div class="prod_img"><img src="([^"]+)@i', $content, $matches) != 0) {
        $addedImages = 0;

        foreach($matches[1] as $newImage) {
            $imageList[$newImage] = $newImage;
            $addedImages++;
        }

        if ($addedImages) {
            echo $addedImages, " new images added\n";
        }
    }

    echo "\n\n";
}

//added 1 for the $level var in recurse_hierarchy()
file_put_contents('hierarchy.html', recurse_hierarchy($breadcrumbMap, 1));

// Extract the images that we found
if (! empty($imageList)) {
    echo "Found ", count($imageList), " product images, extracting...\n";

    if (! file_exists('images')) {
        if (! mkdir('images', 0777)) {
            echo "Wasn't able to create the directory 'images'! No cool pics for you :(\n";
            die;
        }
    }

    foreach($imageList as $imageUrl) {
        // Check the image URL for a predictable filename
        if (! preg_match('@/([^/]+?\.(gif|png|jpe?g))([#?]|$)@i', $imageUrl, $match) == 1) {
            continue;
        }

        $filename = strtolower($match[1]);

        // Download the image
        $imageData = file_get_contents($imageUrl);
        if (empty($imageData)) {
            continue;
        }

        echo $filename, "\n";
        file_put_contents('images/' . $filename, $imageData);
    }
    echo "\n";
}

//function that generates the link list; $hierarchy is an array with info on the links and their visible names and $level sets the level of the lists, starting at 1 (see above)
function recurse_hierarchy($hierarchy, $level)
{
    if($level == 1 && superFunModeActivated()) {
        echo "+===========================+\n";
        echo "| SUPER FUN MODE ACTIVATED! |\n";
        echo "+===========================+\n";
        $key = array_keys($hierarchy['children'])[0];
        $hierarchy = $hierarchy['children'][$key];
    }

    // Initialize $html_string
    $html_string = '';
    //generates each link as part of a list by pulling the url and its visible name from the $hierarchy array
    if (isset($hierarchy['url']) && isset($hierarchy['name'])) {
        //once the list depth reaches 4, all links at this depth are italicized.  why 4 and not 3, no idea (Really? --Nick)
        if ($level == 4) { 
            //generates li option with italic styling
            $html_string = "\n<li><em><a href=\"".$hierarchy['url']."\">".$hierarchy['name']."</a></em>";
        } else { 
            //generates li option with normal styling (seen before lvl 4(3))
            $html_string = "\n<li><a href=\"".$hierarchy['url']."\">".$hierarchy['name']."</a>";
        }
    }
    //generates the ul at each level as long as there's one or more links
    if (!empty($hierarchy['children'])) {
        $html_string .= "\n<ul>";
        //creates the visible links in the html document by having the function call itself to create a list and visible link to display, along with its level; this level is preserved through list generation for multiple items
        foreach ($hierarchy['children'] as $child) {
            $html_append = recurse_hierarchy($child, $level+1);
            $html_string .= $html_append;
            
        }
        //closes a ul
        $html_string .= "\n</ul>";
    }
    //closes an li once its posted
    if (isset($hierarchy['url']) && isset($hierarchy['name'])) {
        $html_string .= "\n</li>";
    }
    //returns the completed list of links organized by hierarchy
    return $html_string;
}

function superFunModeActivated()
{
    return mt_rand(0, 1);
}
