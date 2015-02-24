<?php

define('MAX_PAGES', 20);

$url = 'http://www.tronixweb.com/';

// List of URLs to be crawled
$urlList = array(
    $url
);

for($page=0; $page<count($urlList) && $page<MAX_PAGES; $page++) {
    sleep(1);

    echo $urlList[$page], "\n";

    $content = @file_get_contents($urlList[$page]);
    if (! $content) {
        continue;
    }

    if (preg_match_all('@<a\b[^>]*?href="(https?://[^/"]*?\btronixweb\.com/[^>"]+)"[^>]*>@i', $content, $matches) != 0) {
        $addedLinks = 0;

        foreach($matches[1] as $newUrl) {
            if (! in_array($newUrl, $urlList)) {
                $urlList[] = $newUrl;
                $addedLinks++;
            }
        }

        echo $addedLinks, " new URLs added\n";
    }

    if (preg_match('@<div id="breadcrumbs">[^<a([\s\S]+?)]>([\s\S]+?)[^</a>]</div>@', $content, $matches) != 0) {
    //this brings back the entire link and visible text of link: if (preg_match('@<div id="breadcrumbs">([\s\S]+?)</div>@', $content, $matches) != 0) {

        echo $matches[1];
    }

    echo "\n";
}

print_r($urlList);

//on each pass, find breadcrumbs id and echo as plaintext to screen

    //<div id="extra_links">
    //    <div id="breadcrumbs"><a href="http://www.tronixweb.com/store/index.html">Home</a><span>&gt;</span><a href="http://www.tronixweb.com/store/3ds.html">Nintendo 2DS/3DS</a></div>
    //</div>
