<?php
// A script that returns a list of media that a specific screen should display
// for reference: http://docs.aws.amazon.com/aws-sdk-php/guide/latest/service-dynamodb.html
// and also useful: http://docs.aws.amazon.com/amazondynamodb/latest/developerguide/AppendixSampleDataCodePHP.html

// Load my configuration
require '../vendor/autoload.php';
$datastring = file_get_contents('../config.json');
$config = json_decode($datastring, true);
$debug = true;

// don't want to print debug through web server in general
$debug = false; 
if (!isset($_SERVER['HTTP_HOST'])) {
    $debug = true; 
} else {
    if (isset($_REQUEST['debug'])) {$debug = true;}
}


// You'll need to edit this with your config file
// make sure you specify the correct region as dynamo is region specific
use Aws\Common\Aws;
$aws = Aws::factory('/usr/www/html/photo-display/php/amz_config.json');
$client = $aws->get('DynamoDb');

//Use MY SQL - this include assumes that $config has been loaded 
include 'my_sql.php';

/////////////////////////////////////////////////
// We should have been sent a screen_id & region request variables
// Future functionality:
// 	See if there are filters for this region/screen
// 	See if this is video media that needs to be synchronized

$sql = "SELECT media_path FROM media_files WHERE shown=0 ORDER BY rnd_id LIMIT 25;";
$send_media = query_to_array($sql, &$mysqli);

$usql = '';
foreach ($send_media as $i=>$media_path) {
	$usql .= sqlq($media_path,0) . ',';
}

// Always reset things that were sent but not congfirmed
$sql = 'UPDATE media_files SET shown=2 WHERE media_path IN (' . $usql . ');';
if ($debug) {echo "Running: $sql\n";}
if (!$mysqli->query($sql)) {die("Insert Failed: (" . $mysqli->errno . ") " . $mysqli->error);}

//Send this list of files to the caller
echo json_encode($send_media);




?>
