<?php

define('MAX_PAGES', 20);

$url = 'http://www.tronixweb.com/';

// List of URLs to be crawled
$urlList = array(
    $url
);

// List of product images that we found on this site
$imageList = array();

$breadcrumbMap = array();

// Crawl HTML pages
for($page=0; $page<count($urlList) && $page<MAX_PAGES; $page++) {
    // Throttle the scan so we don't overwhelm the remote server
    sleep(1);

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
        // separating a string into an array on the span tag with $gt (>) and putting it in $match[1]
        $breadcrumbSections = explode('<span>&gt;</span>', $match[1]); 
        // assigning $mapPointer to $breadcrumbMap the & is accepting a reference to $breadcrumbMap as its parameter
        $mapPointer = &$breadcrumbMap;
        //starting a foreach loop and renaming each array $section
        foreach($breadcrumbSections as $section) {
            // grabbing the breadcrumb link and name from the anchor tag 
            if (preg_match('@<a href="([^>"]+)">([^<]+)</a>@', $section, $breadcrumbMatch) == 1) {
                // list is used to assign names to varialbes
                list($nothing, $crumbUrl, $crumbName) = $breadcrumbMatch;
                // checking if $mapPointer -> children is empty
                if (empty($mapPointer['children'])) {
                    // if empty setting it to the array()
                    $mapPointer['children'] = array();
                }
                // checking if $mapPointer -> children -> $crumbUrl is empty
                if (empty($mapPointer['children'][$crumbUrl])) {
                    // setting the variables url, children and name
                    $mapPointer['children'][$crumbUrl] = array(
                        'url'       => $crumbUrl,
                        'children'  => array(),
                        'name'      => $crumbName,
                    );
                }
                // passing $mapPointer -> children -> $crumbUrl as a reference to the varialbe $mapPointer
                $mapPointer = &$mapPointer['children'][$crumbUrl];
            }
        }
        // sanitizing $breadcrumbs by turning ASCII code into characters and removing all tags inside the variable
        $breadcrumb = html_entity_decode(strip_tags($match[1]));
        echo '» ', preg_replace('@\s*>\s*@', ' › ', $breadcrumb), "\n";
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

print_r($breadcrumbMap);


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
