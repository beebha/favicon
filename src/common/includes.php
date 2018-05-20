<?php
/**
 * This file is a common file that includes all php files for the Favicon Finder application's API
 */

// utility files
require_once(dirname(__FILE__) . '/../utils/DBUtils.php');

// exception files
require_once(dirname(__FILE__) . '/../exceptions/APIException.php');

// Favicon files
require_once(dirname(__FILE__) . '/../favicon/FaviconController.php');
require_once(dirname(__FILE__) . '/../favicon/FaviconService.php');
require_once(dirname(__FILE__) . '/../favicon/FaviconQuery.php');