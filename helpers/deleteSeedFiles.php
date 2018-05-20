<?php

require_once __DIR__.'/../src/common/includes.php';

/**
 * Utility to delete created seed files in case error on client side
 *
 * Parameters: NONE
 * EX: deleteSeedFiles.php
 *
 * Script must be run from command line as follows:
 * - Go to the directory: cd /favicon/helpers
 * - Run file with default website URL: php -d safe_mode=Off deleteSeedFiles.php
 *
 */

deleteSeedFiles();

/***********************************************************************************************************************/

function deleteSeedFiles()
{
    $directory = __DIR__.'/../src/data';
    $fileCount = 0;
    $files = glob($directory . '/seed*.csv');

    if ($files !== false)
    {
        $fileCount = count($files);
    }

    print "Found $fileCount seed files";
    FaviconService::deleteCreatedCSVFiles($fileCount);
}
