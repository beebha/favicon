<?php
/**
 * Class FaviconQuery
 *
 * A class that builds queries to be executed for the Favicon
 */
class FaviconQuery
{
    public static function getInsertUpdateFaviconInfoQuery($websiteUrl, $faviconUrl)
    {
        return "INSERT INTO favicon_info
                (website_url, favicon_url, create_date) values (" .
                DBUtils::getDBValue(DBUtils::DB_STRING, $websiteUrl) . "," .
                DBUtils::getDBValue(DBUtils::DB_STRING, $faviconUrl) . ",
                CURRENT_TIMESTAMP)
                ON DUPLICATE KEY UPDATE
                favicon_url = " . DBUtils::getDBValue(DBUtils::DB_STRING, $faviconUrl) . ",
                modify_date = CURRENT_TIMESTAMP";
    }

    public static function getFaviconInfoQuery($websiteUrl)
    {
        return "SELECT favicon_url 
                FROM favicon_info 
                WHERE website_url = " . DBUtils::getDBValue(DBUtils::DB_STRING, $websiteUrl);
    }

    public static function createBulkFaviconInfoQuery(array $urls)
    {
        $queriesToBeExecuted = array();
        foreach($urls as $singleUrl)
        {
            $websiteUrl = $singleUrl['websiteUrl'];
            $faviconUrl = $singleUrl['faviconUrl'];

            $query = "INSERT IGNORE INTO favicon_info
                (website_url, favicon_url, create_date) values (" .
                DBUtils::getDBValue(DBUtils::DB_STRING, $websiteUrl) . "," .
                DBUtils::getDBValue(DBUtils::DB_STRING, $faviconUrl) . ",
                CURRENT_TIMESTAMP)";
            $queriesToBeExecuted[] = $query;
        }

        return $queriesToBeExecuted;
    }
}
 