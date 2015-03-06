<?php

// Debug message
echo "Right now, it's ", date('F j, Y, g:i A'), "\n\n";

// hashmap of cached page expiration times
$cacheTimes = load_cache();
$testUrl = "http://www.tronixweb.com/";

// create cache folder
if (! file_exists('cache')) {
    if (! mkdir('cache', 0777)) {
        echo "Wasn't able to create the directory 'cache'! No quick loading for you :(\n";
        die;
    }
}

$test_page = check_cache($cacheTimes, $testUrl);

function check_cache(&$cacheTimes, $url)
{
    $key = md5($url);

    // Determine whether or not we'll need to write the cache
    $cacheChanged = false;

    // Initialize the key if it's not set because our expiration mechanism will handle writing the cached file
    if (! isset($cacheTimes[$key])) {
        $cacheTimes[$key] = 0;
    }

    if (time() > $cacheTimes[$key]) {  // expired, so must revalidate
        echo $key, ' expired on ', date('F j, Y, g:i A', $cacheTimes[$key]) , ", revalidating\n";
        $response = my_file_get_contents($url, true); // Get the page, plus the response's HTTP headers

        // extract Expires value from headers
        list($header, $page) = explode("\r\n\r\n", $response, 2);

        // Cache the target page
        file_put_contents('cache/'.$key, $page);

        // Get ready to step through each header line
        $lines = explode("\r\n", $header);

        // Step through each line of the header to determine the cache time
        foreach ($lines as $line) {
            if (strpos($line, 'Expires') === 0) {
                echo "Expires detected\n";
                $cacheTime = time() + (60 * 60 * 24); // Would use the Expires value here instead
            } elseif (strpos($line, 'Cache-Control') === 0) {
                echo "Cache-Control detected\n";
                $cacheTime = time() + (60 * 60 * 24); // Would use the Cache-Control: max-age value here instead
            } else {
                $cacheTime = time() + (60 * 60 * 24); // Cache one day (60 seconds * 60 minutes * 24 hours)
            }

            if ($cacheTime > $cacheTimes[$key]) {
                $cacheTimes[$key] = $cacheTime;
                $cacheChanged = true;
                echo 'Now expires on ', date('F j, Y, g:i A', $cacheTime), "\n";
            }
        }
    } else {
        // load page from cache
        $page = file_get_contents("cache/".$key); // Don't need my_file_get_contents() here because we're grabbing a local file
    }

    if ($cacheChanged) {
        save_cache($cacheTimes);
    }

    return $page;
}

function save_cache($cacheTimes)
{
    $output = '';
    foreach($cacheTimes as $key => $value) {
        $output .= $key . ',' . $value . "\n";
    }

    file_put_contents('cacheTimes.txt', $output);
}

function load_cache()
{
    $cache = array();

    $content = @file_get_contents('cacheTimes.txt');
    if (empty($content)) {
        echo "No cache file exists exists yet\n";
        return $cache;
    }

    // Find all valid cache lines
    if(preg_match_all('@^([^,]+),(\d+)$@m', $content, $matches) != 0) {
        $keys   = $matches[1];
        $values = $matches[2];

        echo "Loaded cache successfully:\n";

        // Populate the new cache
        for($i=0; $i<count($keys); $i++) {
            echo $keys[$i], ' expires on ', date('F j, Y, g:i A', $values[$i]), "\n";
            $cache[$keys[$i]] = $values[$i];
        }

        echo "\n\n";
    }

    return $cache;
}

function my_file_get_contents($url, $includeHeader=false)
{
    $curl = curl_init();

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    if ($includeHeader) {
        curl_setopt($curl, CURLOPT_HEADER, true);
    }

    $content = curl_exec($curl);
    curl_close($curl);

    return $content;
}
