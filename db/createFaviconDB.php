<?php

/**
 * Create MySql DB via command line, ensure that server is running
 *
 * Parameters: NONE
 * EX: createFaviconDB.php
 *
 * Script must be run from command line as follows:
 * - Go to the directory: cd /favicon/db
 * - Run file: php -d safe_mode=Off createFaviconDB.php
 *
 */

if (!$link = mysqli_connect('127.0.0.1', 'root', 'root', NULL, 8889, ':/Applications/MAMP/tmp/mysql/mysql.sock')) {
    print 'Could not connect to mysql';
    exit;
}

// drop db favicon if it exists
$dropDBSql = "DROP DATABASE IF EXISTS favicon";

mysqli_query($link, $dropDBSql);

print "Dropped DATABASE favicon\r\n";

// create db favicon
$createDBSql = 'CREATE DATABASE favicon';

if (mysqli_query($link, $createDBSql)) {
    print "Database favicon created successfully\r\n";
} else {
    print "Error creating database: " . mysqli_error($link) . "\r\n";
    exit;
}

if (!$link = mysqli_connect('127.0.0.1', 'root', 'root', 'favicon', 8889, ':/Applications/MAMP/tmp/mysql/mysql.sock')) {
    print "Could not connect to mysql\r\n";
    exit;
}

if (!((bool)mysqli_query($link, "USE favicon"))) {
    print "Could not select database\r\n";
    exit;
}

// drop table favicon_info if it exists
$dropTableSql = "DROP TABLE IF EXISTS favicon_info";

mysqli_query($link, $dropTableSql);

print "Dropped TABLE favicon_info\r\n";

// create table favicon_info
$createTableSql = "CREATE TABLE favicon_info (
                      favicon_id int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
                      website_url varchar(255) NOT NULL,
                      favicon_url varchar(255) NOT NULL,
                      create_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                      modify_date datetime,
                      UNIQUE(website_url)
                    ) ENGINE=InnoDB DEFAULT CHARSET=latin1";

if (mysqli_query($link, $createTableSql)) {
    print "Created TABLE favicon_info successfully\r\n";
} else {
    print "Error creating table: " . mysqli_error($link) . "\r\n";
    exit;
}