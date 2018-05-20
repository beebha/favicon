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
        self::debug("FaviconService -> getFaviconDetails");

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
        self::debug("FaviconService -> createTrimmedCSVFile");

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
            if(!empty($websiteURL)) {
                $websites[] = $websiteURL;
                $lineCount++;
            }
        }

        file_put_contents(__DIR__."/../data/mainSeed.csv", implode(PHP_EOL, $websites));

        fclose($handle);

        $linesInEachFile = 1;
        $filesToCreateCount = $maxLineCount/$linesInEachFile;

        self::debug("filesToCreateCount: $filesToCreateCount");

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
        self::debug("FaviconService -> createCSVFilesForSeeding");

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

        self::debug("Total number of files created for seeding: $fileCount");

        $totalTimeTaken = microtime(true) - $start;

        self::debug("Total time taken for creating files for seeding: $totalTimeTaken");

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
        $start = microtime(true);

        $error = NULL;
        $url = NULL;
        $fileName = __DIR__ . "/../data/seed".$seedNumber.".csv";

        $websites = file($fileName);

        for($i=0; $i < count($websites); $i++)
        {
            $websiteURL = trim($websites[$i]);
            $redirectURLDetails = self::getRedirectURL($websiteURL, 10);
            $isWebsiteURLValid = is_null($redirectURLDetails['error']);

            self::debug("Website $websiteURL is ". ($isWebsiteURLValid ? "valid" : "invalid"));

            if($isWebsiteURLValid) {
                $fullValidWebsiteUrl = $redirectURLDetails['url'];
                $faviconUrlDetails = self::getFaviconURLDetails($fullValidWebsiteUrl);
                $faviconUrl = $faviconUrlDetails['faviconUrl'];
                $isFaviconUrlValid = $faviconUrlDetails['isValid'];
                $timeTaken = $faviconUrlDetails['timeTaken'];

                self::debug("Favicon URL $faviconUrl is ". ($isFaviconUrlValid ? "valid" : "invalid"));
                self::debug("Time Taken for favicon url: $faviconUrl | $timeTaken");

                if(!$isFaviconUrlValid) {
                    $error = "Favicon URL: $faviconUrl is invalid";
                } else {
                    $url = array('websiteUrl' => $fullValidWebsiteUrl, 'faviconUrl' => $faviconUrl);
                }

            } else {
                $error = "Website URL: $websiteURL is invalid";
            }
        }

        $createFaviconInfoQuery = FaviconQuery::createFaviconInfoQuery($url['websiteUrl'], $url['faviconUrl']);
        DBUtils::getInsertUpdateDeleteExecutionResult($createFaviconInfoQuery);

        $totalTimeTaken = microtime(true) - $start;

        self::debug("Total time taken to process seed".$seedNumber.".csv is $totalTimeTaken");

        // return a results array
        return array(
            "status" => empty($error),
            "errorMsg" => $error
        );
    }

    /**
     * Method that deletes all files created for seeding including the main seed file.
     *
     * @param $fileCount
     * @return array
     */

    public static function deleteCreatedCSVFiles($fileCount)
    {
        self::debug("FaviconService -> deleteCreatedCSVFiles");

        for($i=0; $i < $fileCount; $i++)
        {
            $fileName = __DIR__ . "/../data/seed".$i.".csv";
            // delete the seed file
            unlink($fileName);
        }

        // delete the main seed file
        unlink(__DIR__ . "/../data/mainSeed.csv");

        self::debug("All files created for seeding DB have been deleted");

        // return a results array
        return array(
            "status" => TRUE
        );
    }

    private function getFaviconURLDetails($fullWebsiteUrl)
    {
        $start = microtime(true);

        $websiteUrlDetails = parse_url($fullWebsiteUrl);
        $websiteUrl = $websiteUrlDetails['scheme'] . "://". $websiteUrlDetails['host'];
        $faviconRelativeUrl = "/favicon.ico";

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        @$dom->loadHTMLFile($websiteUrl);

        $links = $dom->getElementsByTagName('link');
        $linksCount = $links->length;

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

        if(empty($faviconRelativeUrl)) {
            $faviconUrl = $websiteUrl . "/favicon.ico";
        } else if($faviconRelativeUrl[0] === "/" && $faviconRelativeUrl[1] === "/") {
            $faviconUrl = str_replace("//", $websiteUrlDetails['scheme'] . "://", $faviconRelativeUrl);
        } else if($faviconRelativeUrl[0] === "/") {
            $faviconUrl = $websiteUrl . $faviconRelativeUrl;
        } else {
            $faviconUrl = trim($faviconRelativeUrl);
        }

        $isFaviconURLValid = self::isURLValid($faviconUrl);

        $timeTaken = microtime(true) - $start;

        return array("faviconUrl" => $faviconUrl, "isValid" => $isFaviconURLValid, "timeTaken" => $timeTaken);
    }

    private function updateLatestFaviconDetails($websiteUrl)
    {
        self::debug("FaviconService -> updateLatestFaviconDetails");

        $errorMsg = NULL;
        $faviconUrlDetails = self::getFaviconURLDetails($websiteUrl);
        $faviconUrl = $faviconUrlDetails['faviconUrl'];
        $isFaviconUrlValid = $faviconUrlDetails['isValid'];
        $timeTaken = $faviconUrlDetails['timeTaken'];

        self::debug("Favicon URL $faviconUrl is ". ($isFaviconUrlValid ? "valid" : "invalid"));
        self::debug("Time Taken for favicon url: $faviconUrl | $timeTaken");

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
        $urlExists = true;
        $headers = array_change_key_case(get_headers($url, 1), CASE_LOWER);

        if(stripos(@$headers['content-type'], "image") === FALSE)
        {
            $urlExists = false;
            self::debug("Favicon URL content-type is not an image");
            self::debug(print_r($headers, true));
        }

        return $urlExists;
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
        // set the timeout value to trying to connect to a site by specified timeout value in seconds
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_exec($ch);

        // Check if any error occurred
        if(curl_errno($ch)) {
            $error = curl_error($ch);
        } else {
            $url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            if(substr($url, -1) == '/') {
                $url = substr($url, 0, -1);
            }
        }

        curl_close($ch);

        return array('url' => trim($url), 'error' => $error);
    }

    private function debug($msg)
    {
        date_default_timezone_set('America/New_York');
        // check if log exists, create if it does not exist
        $logDirName = __DIR__ ."/../../logs";

        if(is_dir($logDirName) === false )
        {
            mkdir($logDirName, 0777, true);
        }

        $logFileName = $logDirName. '/favicon_log_' .date('d-M-Y'). '.log';
        file_put_contents($logFileName, $msg . "\n", FILE_APPEND);
    }
}