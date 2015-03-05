<?php

// hashmap of cached page expiration times
$cacheTimes = array();
$testUrl = "http://www.tronixweb.com/";
/* Test CODE. Delete this later!!! */
date_default_timezone_set("UTC");
/* END OF TEST CODE */

// create cache folder
if (! file_exists('cache')) {
    if (! mkdir('cache', 0777)) {
        echo "Wasn't able to create the directory 'cache'! No quick loading for you :(\n";
        die;
    }
}

$test_page = check_cache($testUrl);
save_cache();

function check_cache($url)
{
    $key = md5($url);
    $cacheTimes[$key] = 4000;
    echo '$cacheTimes[$key] = '.$cacheTimes[$key];
    if (true) {   //(isset($cacheTimes[$key])) {
        if (time() > $cacheTimes[$key]) {  // expired, so must revalidate
            $page = my_file_get_contents($url);
            print("\nNew Expiration: ".strtotime("now + 24 hours")."\n");
            // extract Expires value from headers
            $headers = get_headers($url);
            foreach ($headers as $header) {
                if (strpos($header, "Expires") == 0) {

                } elseif (strpos($header, "Cache-Control") == 0) {
                
                } else {
                    echo "Cache time: ";
                    $cacheTimes[$key] = strtotime("now + 24 hours");
                }
            }
        } else {
            // load page from cache
            $page = file_get_contents("cache/".$key); // should i be using my_file_get_contents() ?
        }
    } else {
        // first time getting this page!
        $page = my_file_get_contents($url);
        file_put_contents("cache/".$key, $page);
    }
    return $page;
}

function save_cache() {
    file_put_contents("cacheTimes.txt", implode(",", $cacheTimes));
}

function load_cache($cacheFile) {

}

function my_file_get_contents($url) {
    $curl = curl_init();

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    $content = curl_exec($curl);
    curl_close($curl);

    return $content;
}