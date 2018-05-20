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

    /**
     * Method that creates the CSV files required for seeding the DB.
     *
     * @return array
     */
    public static function createCSVFilesForSeeding()
    {
        $singleFileContent = array();

        $count = 0;
        $fileCount = 0;

        foreach (new SplFileObject(__DIR__ . "/../data/top-million.csv") as $line)
        {
            if ($count <= 50) {
                $websiteDetails = explode(",", $line);
                $websiteURL = trim($websiteDetails[1]);

                // only add valid websites
                $redirectURLDetails = self::getRedirectURL($websiteURL, 5);
                $isWebsiteURLValid = is_null($redirectURLDetails['error']);

                if($isWebsiteURLValid) {
                    $singleFileContent[] = $redirectURLDetails['url'];;
                }

                if(count($singleFileContent) == 10) {
                    file_put_contents(__DIR__."/../data/seed".$fileCount.".csv", implode(PHP_EOL, $singleFileContent));
                    $fileCount++;
                    $singleFileContent = array();
                }

                $count++;

            } else {
                break;
            }
        }

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
        $errors = array();
        $urls = array();
        $timeTakenInfo = array();
        $fileName = __DIR__ . "/../data/seed".$seedNumber.".csv";

        foreach (new SplFileObject($fileName) as $websiteURL)
        {
            $fullValidWebsiteUrl = trim($websiteURL);
            $faviconUrlDetails = self::getFaviconURLDetails($fullValidWebsiteUrl);
            $faviconUrl = $faviconUrlDetails['faviconUrl'];
            $isFaviconUrlValid = $faviconUrlDetails['isValid'];
            $timeTaken = $faviconUrlDetails['timeTaken'];
            $timeTakenInfo[] = "$faviconUrl | $timeTaken";

            if(!$isFaviconUrlValid) {
                $errors[] = "$faviconUrl is invalid";
            } else {
                $urls[] = array('websiteUrl' => $fullValidWebsiteUrl, 'faviconUrl' => $faviconUrl);
            }
        }

        $createBulkFaviconInfoQueries = FaviconQuery::createBulkFaviconInfoQuery($urls);
        DBUtils::getInsertUpdateDeleteBulkExecutionResult($createBulkFaviconInfoQueries);

        // delete the seed file
        unlink($fileName);

        // return a results array
        return array(
            "status" => TRUE,
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

        print "Website URL : " . $websiteUrl. "\r\n";

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
                } else if (strcasecmp($item->getAttribute("rel"),"icon") === 0 && stripos($href, "favicon") !== FALSE) {
                    $faviconRelativeUrl = $href;
                    break;
                }
            }
        }

        if($faviconRelativeUrl[0] === "/" && $faviconRelativeUrl[1] === "/") {
            $faviconUrl = str_replace("//", "http://", $faviconRelativeUrl);
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
        $errorMsg = NULL;
        $faviconUrlDetails = self::getFaviconURLDetails($websiteUrl);
        $faviconUrl = $faviconUrlDetails['faviconUrl'];
        $isFaviconUrlValid = $faviconUrlDetails['isValid'];

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
}