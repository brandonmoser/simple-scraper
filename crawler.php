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
        if (preg_match('@<title>([^<]+)</title>@i', $content, $match) !=0) {
           $newTitle = $match[1];      
        }

        echo $addedLinks, " new URLs added\n";
        echo $newTitle, " Page Title";
    }

    echo "\n";
}

print_r($urlList);
