<?php
// A script that lets you control settings for each screen

// Load my configuration
require '../vendor/autoload.php';
$datastring = file_get_contents('../master_config.json');
$config = json_decode($datastring, true);

// don't want to print debug through web server in general
$debug = false; 
if (!isset($_SERVER['HTTP_HOST'])) {
    $debug = true; 
} else {
    if (isset($_REQUEST['debug'])) {$debug = true;}
}

// How many to queue at a time
$queue_length = 8;
if (isset($_REQUEST['length'])) {$queue_length = $_REQUEST['length'];}


//Use MY SQL - this include assumes that $config has been loaded 
include 'my_sql.php';
include 'curl_functions.php';

// Check that we've got a valid token
if (isset($_REQUEST['enc'])) {
    if (! check_token($_REQUEST['enc'], $mysqli)) {
        echo 'bad token passed'; 
        exit;
    }
} else {
    echo 'no token passed'; 
    exit;
}

// You'll need to edit this with your config file
// make sure you specify the correct region as dynamo is region specific
use Aws\Common\Aws;
$aws = Aws::factory('/usr/www/html/photo-display/php/amazon_config.json');
$client = $aws->get('DynamoDb');



?>
