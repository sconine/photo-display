<?php
// A script that returns a list of media that a specific screen should display
// for reference: http://docs.aws.amazon.com/aws-sdk-php/guide/latest/service-dynamodb.html
// and also useful: http://docs.aws.amazon.com/amazondynamodb/latest/developerguide/AppendixSampleDataCodePHP.html

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
$queue_length = 5;
if (isset($_REQUEST['length'])) {$queue_length = $_REQUEST['length'];}


// You'll need to edit this with your config file
// make sure you specify the correct region as dynamo is region specific
use Aws\Common\Aws;
$aws = Aws::factory('/usr/www/html/photo-display/php/amazon_config.json');
$client = $aws->get('DynamoDb');

//Use MY SQL - this include assumes that $config has been loaded 
include 'my_sql.php';

/////////////////////////////////////////////////
// We should have been sent a screen_id & region request variables
// Future functionality:
// 	See if there are filters for this region/screen
// 	See if this is video media that needs to be synchronized

$sql = "SELECT media_path, media_type FROM media_files WHERE shown=0 ORDER BY rnd_id LIMIT " . $queue_length . ";";
$send_media = query_to_array($sql, $mysqli);

// If we didn't get anything just return 25 - sync_media.php needs to run to reset
if (count($send_media) == 0) {
	$sql = "SELECT media_path, media_type FROM media_files ORDER BY rnd_id LIMIT " . $queue_length . ";";
	$send_media = query_to_array($sql, $mysqli);
}

$usql = '';
foreach ($send_media as $row) {
	if ($usql != '') {$usql .= ',';}
	$usql .= sqlq($row['media_path'],0);
}

if ($usql != '') {
	// Mark these fields as sent but not congfirmed
	$sql = 'UPDATE media_files SET shown=2 WHERE media_path IN (' . $usql . ');';
	if ($debug) {echo "Running: $sql\n";}
	if (!$mysqli->query($sql)) {die("Insert Failed: (" . $mysqli->errno . ") " . $mysqli->error);}
}

//Send this list of files to the caller
echo json_encode($send_media);




?>
