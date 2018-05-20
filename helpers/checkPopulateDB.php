<?php

require_once __DIR__.'/../src/common/includes.php';

/**
 * Check seeding and population of data service via command line
 *
 * Parameters: NONE
 * EX: checkPopulateDB.php
 *
 * Script must be run from command line as follows:
 * - Go to the directory: cd /favicon/src/helpers
 * - Run file with default website URL: php -d safe_mode=Off checkPopulateDB.php
 *
 */

populateDB();

/***********************************************************************************************************************/

function populateDB()
{
    $results = FaviconService::createCSVFilesForSeeding();
    $fileCount = $results['data']['seedCount'];

    for($i = 0; $i < $fileCount; $i++)
    {
        $results = FaviconService::populateDBWithSeedFile($i);
        print json_encode($results, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
}
