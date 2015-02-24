<?php

define('MAX_PAGES', 20);

$url = 'http://www.tronixweb.com/';

// List of URLs to be crawled
$urlList = array(
    $url
);

$imageList = array();

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

    // IMAGE CRAWLING
    if (preg_match_all('@<div class="prod_img"><img src="([\s\S]+?)"@i', $content, $matches) != 0) {
        $addedImages = 0;

        foreach($matches[1] as $newImage) {
            $imageList[$newImage] = $newImage;
            $imageString = file_get_contents($newImage);
            preg_match('@\/([^\/]+?\.(gif|png|jpg))@i', $newImage, $tmp);

            if (! file_exists('images')) {
                mkdir('images', 0777);
            }
            file_put_contents("images/".($tmp[1]), $imageString);
            $addedImages++;
        }

        echo $addedImages, " new images added\n";
    }

    echo "\n";
}

print_r($urlList);
print_r($imageList);
