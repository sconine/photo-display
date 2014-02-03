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

use Aws\Common\Aws;

// You'll need to edit this with your config
$aws = Aws::factory('/usr/www/html/photo-display/php/amz_config.json');

// Connect to local MySQL database
$mysqli = new mysqli($config['mysql']['host'], $config['mysql']['user'], $config['mysql']['password'], $config['mysql']['database']);
if ($mysqli->connect_errno) {
	echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
	die;
}
if ($debug) {
	echo $mysqli->host_info . "\n";
	echo 'Connected to MySQL'. "\n";
}

// Build the media_files table schema on the fly
$sql = 'CREATE TABLE IF NOT EXISTS media_files ('
. ' id INTEGER AUTO_INCREMENT UNIQUE KEY, '
. ' media_path varchar(2000) NOT NULL, '
. ' rnd_id int NULL, '
. ' PRIMARY KEY (media_path), '
. ' INDEX(id));';
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

// Loop through files and add to our local index
foreach ($iterator as $s3_item) {
	$sql = 'INSERT IGNORE INTO media_files (media_path, rnd_id) VALUES ('
		. sqlq($object['Key'],0) . ','
		. '(FLOOR( 1 + RAND( ) *60 )) )';
	if ($debug) {echo "Running: $sql\n";}
	if (!$mysqli->query($sql)) {die("Insert Failed: (" . $mysqli->errno . ") " . $mysqli->error);}
}


// Query helper functions
function sqlq($var, $var_type) {
  if ($var_type == 1) {
    if (is_numeric($var) && !empty($var)) {
      return $var;
    } 
  } else {
    if (!empty($var)) {
      $var = str_replace("'", "''", $var);
      return "'" . $var . "'";
    }
  }
  return 'NULL';
}

?>
