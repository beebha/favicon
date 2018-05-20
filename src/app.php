<?php
/**
 * This file is redirects requests to the appropriate controllers and returns the response as a JSON
 */

// set the default timezone for the application
date_default_timezone_set('America/New_York');

// require the file that includes all files used in the application
require_once 'common/includes.php';

$result = array();

// based on the action, direct to the appropriate Controller class
try {
    if (isset($_REQUEST['action']) && !is_null($_REQUEST['action']))
    {
        $action = $_REQUEST['action'];

        // redirect to appropriate function in favicon controller
        if($action == 'findFavicon') {
            $websiteUrl = $_POST['websiteUrl'];
            $result = FaviconController::findFavicon($websiteUrl);
        }

        if($action == 'createCSVFiles') {
            $result = FaviconController::createCSVFiles();
        }

        if($action == 'populateDB') {
            $seedNumber = intval($_POST['seedNumber']);
            $result = FaviconController::populateDB($seedNumber);
        }
    }
} catch (APIException $ex) {
    $result['error'] = $ex->getMessage();;
    $result['success'] = FALSE;
} catch (Exception $ex) {
    $result['error'] = $ex->getMessage();;
    $result['success'] = FALSE;
}

// set the header content type to JSON, encode the results and return back to client
header("Content-Type: application/json");
header("Content-length: ". strlen(json_encode($result)));
echo json_encode($result);