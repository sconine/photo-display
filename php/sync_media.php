<?php
// Get the media we have stored on S3 and load it into a dynamoDB

require 'vendor/autoload.php';
$datastring = file_get_contents('../config.json');
$config = json_decode($datastring, true);

// don't want to print debug through web server in general
$debug = false; 
if (!isset($_SERVER['HTTP_HOST'])) {
    $debug = true; 
} else {
    if (isset($_REQUEST['debug'])) {$debug = true;}
}

//Use MY SQL - this include assumes that $config has been loaded 
include 'my_sql.php';

// You'll need to edit this with your config
use Aws\Common\Aws;
$aws = Aws::factory('/usr/www/html/photo-display/php/amz_config.json');

// Build the media_files table schema on the fly
// id = increment column for joins if need be in the future
// rnd_id = random ID used for sorting
// shown_int = the state of being shown this is 0 = now shown, 1 = shown, 2 = sent but not confirmed

$sql = 'CREATE TABLE IF NOT EXISTS media_files ('
. ' id INTEGER AUTO_INCREMENT UNIQUE KEY, '
. ' media_path varchar(2000) NOT NULL, '
. ' rnd_id int NULL, '
. ' shown int NOT NULL, '
. ' PRIMARY KEY (media_path), '
. ' INDEX(id), INDEX(shown));';
if (!$mysqli->query($sql)) {die("Table creation failed: (" . $mysqli->errno . ") " . $mysqli->error);}
if ($debug) {echo 'media_files table Exists'. "\n";}

// connect to S3 and get a list of files we are storeing
// Unknown: What is the pratical upper limit to # of files, hoping it is like 1M
$s3_client = $aws->get('s3');

// Set the bucket for where media is stored and retrive all objects
// this is what could get to be a big list
$bucket = 'SConine_Photos';
$media_iterator = $client->getIterator('ListObjects', array(
    'Bucket' => $bucket
    //,'Prefix' => 'Dec-2005'  // this will filter to specific matches
));

// First see if we've shown everything, if so we'll re-randomize
$sql = 'SELECT id FROM media_files WHERE shown=0 LIMIT 50;';
$shown_all = query_to_array($sql, &$mysqli);
if (count($shown_all) < 50) {
	// We've pretty much shown everything to re-randomize and reset (expecting 100,000 files typically)
	$sql = 'UPDATE media_files '
		. ' SET rnd_id=(FLOOR( 1 + RAND( ) *60 )), shown=0;';
	if ($debug) {echo "Running: $sql\n";}
	if (!$mysqli->query($sql)) {die("Insert Failed: (" . $mysqli->errno . ") " . $mysqli->error);}
}

// Always reset things that were sent but not congfirmed
$sql = 'UPDATE media_files SET shown=0 WHERE shown=3;';
if ($debug) {echo "Running: $sql\n";}
if (!$mysqli->query($sql)) {die("Insert Failed: (" . $mysqli->errno . ") " . $mysqli->error);}


// Loop through files and add to our local index
foreach ($iterator as $s3_item) {
	$sql = 'INSERT IGNORE INTO media_files (media_path, rnd_id, shown) VALUES ('
		. sqlq($object['Key'],0) . ','
		. '(FLOOR( 1 + RAND( ) *60 )), 0)';
	if ($debug) {echo "Running: $sql\n";}
	if (!$mysqli->query($sql)) {die("Insert Failed: (" . $mysqli->errno . ") " . $mysqli->error);}
}

?>
