<?php

require_once __DIR__.'/../src/common/includes.php';

/**
 * Check getFaviconDetails service via command line
 *
 * Parameters: url or NONE, if none default URL is used
 * EX: checkGetFaviconDetails.php --url=www.google.com OR checkGetFaviconDetails.php
 *
 * Script must be run from command line as follows:
 * - Go to the directory: cd /favicon/src/helpers
 * - Run file with default website URL: php -d safe_mode=Off checkGetFaviconDetails.php
 * - Run file with specific website URL: php -d safe_mode=Off checkGetFaviconDetails.php --url=www.crayon.co
 *
 */

checkGetFaviconDetails();

/***********************************************************************************************************************/

function checkGetFaviconDetails()
{
    $getLatestFaviconUrl = rand(0,1) === 1;
    $websiteURL = getInputParam();
    $results = FaviconService::getFaviconDetails($websiteURL, $getLatestFaviconUrl);
    print json_encode($results, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}

function getInputParam($default="crayon.co")
{
    $inputParam = getopt(null, ["url:"]);

    if(isset($inputParam["url"])) {
        return $inputParam["url"];
    } else {
        return $default;
    }
}
