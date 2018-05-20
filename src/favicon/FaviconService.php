<?php

/**
 * Class FaviconService
 *
 */
class FaviconService
{
    /**
     * Method that returns favicon details based on the provided web page URL.
     *
     * @param $origWebsiteUrl
     * @param $getLatestFaviconUrl
     * @return array
     * @throws APIException
     */
    public static function getFaviconDetails($origWebsiteUrl, $getLatestFaviconUrl)
    {
        $faviconUrl = NULL;
        $errorMsg = NULL;
        $faviconStatusMsg = NULL;

        // validate website URL
        $websiteUrlDetails = self::getRedirectURL($origWebsiteUrl, 0);
        $isWebsiteURLValid = is_null($websiteUrlDetails['error']);

        if(!$isWebsiteURLValid) {
            throw new APIException("Invalid Website URL: " .$origWebsiteUrl);
        }

        $websiteUrl = $websiteUrlDetails['url'];

        // get existing favicon url
        if(!$getLatestFaviconUrl) {
            $getFaviconInfoQuery = FaviconQuery::getFaviconInfoQuery($websiteUrl);
            $getFaviconInfoResults = DBUtils::getSingleDetailExecutionResult($getFaviconInfoQuery);
            $faviconUrl = $getFaviconInfoResults['favicon_url'];
            $faviconStatusMsg = "Existing favicon was requested. It exists and was retrieved";
        }

        // get latest favicon url if requested or if it doesn't exist in the DB
        if($getLatestFaviconUrl || is_null($faviconUrl)) {
            $faviconDetails = self::updateLatestFaviconDetails($websiteUrl);
            $faviconUrl = $faviconDetails['faviconUrl'];
            $errorMsg = $faviconDetails['errorMsg'];
            $faviconStatusMsg = $getLatestFaviconUrl ?
                "Latest favicon was requested and retrieved" :
                "Existing favicon was requested. It doesn't exist so latest was retrieved";
        }

        // return a results array
        return array(
            "status" => is_null($errorMsg),
            "errorMsg" => is_null($errorMsg) ? "" : $errorMsg,
            "data" => array(
                "websiteUrl" => $origWebsiteUrl,
                "getLatestFaviconUrl" => $getLatestFaviconUrl ? "yes" : "no",
                "faviconUrl" => $faviconUrl,
                "faviconStatusMsg" => $faviconStatusMsg
            )
        );
    }

    public static function createTrimmedCSVFile()
    {
//        $maxLineCount = 200000;
        $maxLineCount = 100;
        $websites = array();

        $file = __DIR__ . "/../data/top-million.csv";
        $lineCount = 0;

        $handle = fopen($file, "r");

        while($lineCount < $maxLineCount) {
            $line = fgets($handle);
            $websiteDetails = explode(",", $line);
            $websiteURL = trim($websiteDetails[1]);
            $websites[] = $websiteURL;
            $lineCount++;
        }

        file_put_contents(__DIR__."/../data/mainSeed.csv", implode(PHP_EOL, $websites));

        fclose($handle);

        $linesInEachFile = 1;
        $filesToCreateCount = $maxLineCount/$linesInEachFile;

//        print "filesToCreateCount: $filesToCreateCount\r\n\n";

        // return files to create count
        return array(
            "mainFileName" => __DIR__."/../data/mainSeed.csv",
            "filesToCreateCount" => $filesToCreateCount,
            "linesInEachFile" => $linesInEachFile
        );
    }

    /**
     * Method that creates the CSV files required for seeding the DB.
     *
     * @return array
     */
    public static function createCSVFilesForSeeding()
    {
        $start = microtime(true);

        $filesToCreateDetails = self::createTrimmedCSVFile();
        $mainFileName = $filesToCreateDetails['mainFileName'];
        $filesToCreateCount = $filesToCreateDetails['filesToCreateCount'];
        $linesInEachFile = $filesToCreateDetails['linesInEachFile'];

        $singleFileContent = array();

        $count = 0;
        $fileCount = 0;;
        $allWebsites = file($mainFileName);

        for($i=0; $i < count($allWebsites); $i++)
        {
            if ($count <= $filesToCreateCount) {

                $websiteURL = trim($allWebsites[$i]);
                $singleFileContent[] = $websiteURL;

                if(count($singleFileContent) == $linesInEachFile) {
                    file_put_contents(__DIR__."/../data/seed".$fileCount.".csv", implode(PHP_EOL, $singleFileContent));
                    $fileCount++;
                    $singleFileContent = array();
                }

                $count++;

            } else {
                break;
            }
        }

//        print "seedCount: $fileCount\r\n\n";

        $timeTaken = microtime(true) - $start;

//        print "timeTaken: $timeTaken\r\n\n";

        // return a results array
        return array(
            "status" => TRUE,
            "errorMsg" => "",
            "data" => array(
                "seedCount" => $fileCount
            )
        );
    }

    /**
     * Method that creates populates the DB with the CSV files created for seeding.
     *
     * @param $seedNumber
     * @return array
     */
    public static function populateDBWithSeedFile($seedNumber)
    {
//        print "populateDBWithSeedFile: $seedNumber\r\n\n";

        $start = microtime(true);

        $errors = array();
        $urls = array();
        $timeTakenInfo = array();
        $fileName = __DIR__ . "/../data/seed".$seedNumber.".csv";

        $websites = file($fileName);

        for($i=0; $i < count($websites); $i++)
        {
            $websiteURL = trim($websites[$i]);
            $redirectURLDetails = self::getRedirectURL($websiteURL, 3);
            $isWebsiteURLValid = is_null($redirectURLDetails['error']);

//            print "Website: $websiteURL\r\n\n";
//            print "Valid: $isWebsiteURLValid\r\n\n";

            if($isWebsiteURLValid) {
                $fullValidWebsiteUrl = $redirectURLDetails['url'];
                $faviconUrlDetails = self::getFaviconURLDetails($fullValidWebsiteUrl);
                $faviconUrl = $faviconUrlDetails['faviconUrl'];
                $isFaviconUrlValid = $faviconUrlDetails['isValid'];
                $timeTaken = $faviconUrlDetails['timeTaken'];

//                print "Time Taken: $faviconUrl | $timeTaken\r\n\n";

                $timeTakenInfo[] = "$faviconUrl | $timeTaken";

                if(!$isFaviconUrlValid) {
                    $errors[] = "Favicon URL: $faviconUrl is invalid";
                } else {
                    $urls[] = array('websiteUrl' => $fullValidWebsiteUrl, 'faviconUrl' => $faviconUrl);
                }
            } else {
                $errors[] = "Website URL: $websiteURL is invalid";
            }
        }

        $createBulkFaviconInfoQueries = FaviconQuery::createBulkFaviconInfoQuery($urls);
        DBUtils::getInsertUpdateDeleteBulkExecutionResult($createBulkFaviconInfoQueries);

        // delete the seed file
        unlink($fileName);

        $timeTaken = microtime(true) - $start;

//        print "Total timeTaken: $timeTaken\r\n\n";

        // return a results array
        return array(
            "status" => empty($errors),
            "errorMsg" => $errors,
            "data" => array(
                "timeTakenInfo" => $timeTakenInfo
            )
        );
    }

    private function getFaviconURLDetails($fullWebsiteUrl)
    {
        $start = microtime(true);

        $websiteUrlDetails = parse_url($fullWebsiteUrl);
        $websiteUrl = $websiteUrlDetails['scheme'] . "://". $websiteUrlDetails['host'];
        $faviconRelativeUrl = "/favicon.ico";

//        print "Website: " . $websiteUrl. "\r\r\n";

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        @$dom->loadHTMLFile($websiteUrl);

        $links = $dom->getElementsByTagName('link');
        $linksCount = $links->length;

//        print "Links count in getFaviconURLDetails: " . $linksCount. "\r\r\n";

        if($linksCount > 0)
        {
            for($i = 0; $i < $linksCount; $i++)
            {
                $item = $links->item($i);
                $href = $item->getAttribute("href");

                if(strcasecmp($item->getAttribute("rel"),"shortcut icon") === 0) {
                    $faviconRelativeUrl = $href;
                    break;
                } else if (strcasecmp($item->getAttribute("rel"),"icon") === 0 &&
                    stripos($href, "favicon") !== FALSE) {
                    $faviconRelativeUrl = $href;
                    break;
                }
            }
        }

//        print "Favicon Relative URL in getFaviconURLDetails: " . $faviconRelativeUrl. "\r\r\n";

        if(empty($faviconRelativeUrl)) {
            $faviconUrl = $websiteUrl . "/favicon.ico";
        } else if($faviconRelativeUrl[0] === "/" && $faviconRelativeUrl[1] === "/") {
            $faviconUrl = str_replace("//", "http://", $faviconRelativeUrl);
        } else if($faviconRelativeUrl[0] === "/") {
            $faviconUrl = $websiteUrl . $faviconRelativeUrl;
        } else {
            $faviconUrl = trim($faviconRelativeUrl);
        }

//        print "Favicon URL in getFaviconURLDetails: " . $websiteUrl. "\r\r\n";

        $isFaviconURLValid = self::isURLValid($faviconUrl);

        $timeTaken = microtime(true) - $start;

        return array("faviconUrl" => $faviconUrl, "isValid" => $isFaviconURLValid, "timeTaken" => $timeTaken);
    }

    private function updateLatestFaviconDetails($websiteUrl)
    {
        $errorMsg = NULL;
        $faviconUrlDetails = self::getFaviconURLDetails($websiteUrl);
        $faviconUrl = $faviconUrlDetails['faviconUrl'];
        $isFaviconUrlValid = $faviconUrlDetails['isValid'];

//        print 'Favicon URL : ' .$faviconUrl. "\r\r\n";
//        print 'Valid? ' . ($isFaviconUrlValid ? "yes" : "no"). "\r\r\n";

        if(!$isFaviconUrlValid) {

            $errorMsg = "Sorry, website $websiteUrl has no favicon URL at $faviconUrl";

        } else {
            $insertUpdateFaviconInfoQuery = FaviconQuery::getInsertUpdateFaviconInfoQuery($websiteUrl, $faviconUrl);
            $insertUpdateFaviconInfoResults = DBUtils::getInsertUpdateDeleteExecutionResult($insertUpdateFaviconInfoQuery);

            if(!$insertUpdateFaviconInfoResults) {
                $errorMsg = "DB Error inserting favicon row";
            }
        }

        return array("faviconUrl" => $faviconUrl, "errorMsg" => $errorMsg);
    }

    private function isURLValid($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        // don't download content - saves time
        curl_setopt($ch, CURLOPT_NOBODY, 1);
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode != 404;
    }

    private function getRedirectURL($url, $timeout)
    {
        $error = NULL;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        // Set to true so that PHP follows any "Location:" header
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // set the timeout value to trying to connect to a site to 10 seconds
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,$timeout);
        curl_exec($ch);

        // Check if any error occurred
        if(curl_errno($ch))
        {
            $error = curl_error($ch);
        } else {
            $url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL); // This is what you need, it will return you the last effective URL
            if(substr($url, -1) == '/') {
                $url = substr($url, 0, -1);
            }
        }

        curl_close($ch);

        return array('url' => trim($url), 'error' => $error);
    }
// TODO
//    private function debug($msg)
//    {
//        // check if log exists, create if it does not exist
//        $logDirName = __DIR__ ."/../../logs";
//        $logFileName = 'test.txt';
//        if(is_dir($dirName) === false )
//        {
//            mkdir($dirName);
//        }
//    }
}