<?php

/**
 * Class FaviconController
 *
 * A controller class that directs calls to @see FaviconService
 */
class FaviconController
{
    /**
     * Method executes call in @see FaviconService::getFaviconDetails
     *
     * @param $websiteUrl
     * @param $getLatestFaviconUrl
     * @return array
     */
    public static function findFavicon($websiteUrl, $getLatestFaviconUrl = FALSE)
    {
        $result = array();

        $resultData = FaviconService::getFaviconDetails($websiteUrl, $getLatestFaviconUrl);

        // return a response array
        $result['success'] = $resultData['status'];
        $result['error'] = $resultData['errorMsg'];
        $result['data'] = $resultData['data'];

        return $result;
    }

    /**
     * Method executes call in @see FaviconService::createCSVFilesForSeeding
     *
     * @return array
     */
    public static function createCSVFiles()
    {
        $result = array();

        $resultData = FaviconService::createCSVFilesForSeeding();

        // return a response array
        $result['success'] = $resultData['status'];
        $result['error'] = $resultData['errorMsg'];
        $result['data'] = $resultData['data'];

        return $result;
    }

    /**
     * Method executes call in @see FaviconService::populateDBWithSeedFile
     *
     * @param $seedNumber
     * @return array
     */
    public static function populateDB($seedNumber)
    {
        $result = array();

        $resultData = FaviconService::populateDBWithSeedFile($seedNumber);

        // return a response array
        $result['success'] = $resultData['status'];
        $result['error'] = $resultData['errorMsg'];
        $result['data'] = $resultData['data'];

        return $result;
    }

    /**
     * Method executes call in @see FaviconService::deleteCreatedCSVFiles
     *
     * @param $fileCount
     * @return array
     */
    public static function deleteFiles($fileCount)
    {
        $result = array();

        $resultData = FaviconService::deleteCreatedCSVFiles($fileCount);

        // return a response array
        $result['success'] = $resultData['status'];

        return $result;
    }
}
