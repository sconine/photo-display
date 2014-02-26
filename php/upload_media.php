<?php
// A script to compare what we have in a local directory
// with what is up on Amazon EC2
$debug = false; 
if (!isset($_SERVER['HTTP_HOST'])) {
    $debug = true; 
} else {
    if (isset($_REQUEST['debug'])) {$debug = true;}
}
// Load my configuration
$localpath = "/Volumes/My Pictures/"; // modify with your local folder
$datastring = file_get_contents('/usr/www/html/photo-display/master_config.json');
$config = json_decode($datastring, true);

$local_files = find_all_files($dir);
var_dump($local_files);
exit;

if ($debug) {echo "datastring: $datastring\n";}
if ($debug) {var_dump($config);}

// You'll need to edit this with your config
require '/usr/www/html/photo-display/vendor/autoload.php';
use Aws\Common\Aws;
$aws = Aws::factory('/usr/www/html/photo-display/php/amazon_config.json');

// connect to S3 and get a list of files we are storeing
// Unknown: What is the pratical upper limit to # of files, hoping it is like 1M
$s3_client = $aws->get('s3');

// Set the bucket for where media is stored and retrive all objects
// this is what could get to be a big list
$bucket = $config['ec2_image_bucket'];
$media_iterator = $s3_client->getIterator('ListObjects', array(
    'Bucket' => $bucket
    //,'Prefix' => 'Dec-2005'  // this will filter to specific matches
));

// Loop through files and sync to our local index
$time = time();
$cnt = 0;
foreach ($media_iterator as $s3_item) {
	// Don't load anything larger than 1GB
	if ($s3_item['Size'] < 1000000000) {
		$file_path = trim($s3_item['Key']);
		// don't bother storing folder names
		if (substr($file_path, -1) == '/') {$file_path = '';}

		if ($file_path != '') {
			$media_type = "";
			$f_ext = strtolower(substr($file_path, -3));
			$f_ext_4 = strtolower(substr($file_path, -4));
			if ($debug) {echo "Extension: " . $f_ext . "\n";}
			if ($f_ext == 'gif') {
				$media_type = "image/gif";
			} elseif ($f_ext == 'jpg' || $f_ext_4 == 'jpeg') {
				$media_type = "image/jpeg";
			} elseif ($f_ext == 'mov') {
				$media_type = "movie/quicktime";
			} elseif ($f_ext_4 == 'mpeg') {
				$media_type = "movie/mpeg";
			} elseif ($f_ext == 'mp4') {
				$media_type = "movie/mp4";
			} elseif ($f_ext == 'cmf') {
				$media_type = "application/screen.comopound.movie";
			} elseif ($f_ext == 'png') {
				$media_type = "image/png";
			}

			// only store the files we care about
			if ($media_type != '') {
				$sql = 'INSERT IGNORE INTO media_files (media_path, media_type, media_size, last_sync, rnd_id, shown) VALUES ('
					. $s3_item['Key'] . ','
					. $media_type . ','
					. $s3_item['Size'] . ','
					. $time . ','
					. '(FLOOR( 1 + RAND( ) *6000000 )), 0) ON DUPLICATE KEY UPDATE last_sync=' . $time . ';';
				if ($debug) {echo "Running: $sql\n";}
				$cnt = $cnt + 1;
			}
		}
	} else {
		if ($debug) {echo "File > 1GB: " . $s3_item['Key'] . " Size: " . $s3_item['Size'] . "\n";}

	}
}

// helper functions
function find_all_files($dir) 
{ 
    $root = scandir($dir); 
    foreach($root as $value) 
    { 
        if($value === '.' || $value === '..') {continue;} 
        if(is_file("$dir/$value")) {$result["$dir/$value"]=1;continue;} 
        
        $flist = find_all_files("$dir/$value");
        if (!empty($flist)) {
        	foreach($flist as $value) 
        	{ 
           	 	$result[$value]=1; 
        	} 
        }
    } 
    return $result; 
} 

?>
