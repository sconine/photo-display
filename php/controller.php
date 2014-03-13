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


// You'll need to edit this with your config file
// make sure you specify the correct region as dynamo is region specific
use Aws\Common\Aws;
$aws = Aws::factory('/usr/www/html/photo-display/php/amazon_config.json');
$client = $aws->get('DynamoDb');

// Need to display screens and screen groups
// Field List: Type, Name, Speed, Show, Screen Group, Last Checkin, Image History, Storage Available, Local IP, Public IP

// Settings panel for a screen should let you modify:
// Speed
// Movies Over-ride Speed
// Show: (o) All Available
//       (o) This folder and Below [    ] GO
//       (o) These images [    ] Browse GO
// Put in Screen Group [         ] GO
// (this moves control to the screen group level)


?>
