<?php
// A script to compare what we have in a local directory
// with what is up on Amazon EC2
if (!isset($_SERVER['HTTP_HOST'])) {
    $debug = true; 
} else {
    if (isset($_REQUEST['debug'])) {$debug = true;}
}
$debug = false; 

// Load my configuration
$localpath = "/Volumes/My Pictures"; // modify with your local folder
$subpath = "/xmas"; // modify with your local folder
$datastring = file_get_contents('/usr/www/html/photo-display/master_config.json');
$config = json_decode($datastring, true);

// Get all the files we have locally and load into a dictionary in memory
$local_files = find_all_files($localpath . $subpath);

if ($debug) {echo "datastring: $datastring\n";}
if ($debug) {echo "Local files----------\n";}
if ($debug) {var_dump($local_files);}
if ($debug) {echo "Config----------\n";}
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

// Loop through files on EC2 and build a list of what we have
$time = time();
$cnt = 0;
$remote_files = array();
foreach ($media_iterator as $s3_item) {
	$remote_files[trim($s3_item['Key'])] = 1;
}
if ($debug) {echo "EC2 remote files----------\n";}
if ($debug) {var_dump($remote_files);}

foreach ($local_files as $file_path => $i) {
	// Don't load anything larger than 1GB
	if (filesize($file_path) < 1000000000) {
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
				// This is a file we'd like to store see if we have already
				$remote_path = str_replace($localpath . "/", "", $file_path);
				if (!isset($remote_files[$remote_path])) {
					echo "store: $file_path\n";
					
					// When we upload these we also want to store the MD5 and SHA
					// hash of the file for comparison in the future
					// doing both give options
					echo 'MD5 file hash of ' . $file_path . ': ' . md5_file($file_path);
					echo 'SHA1 file hash of ' . $file_path . ': ' . sha1_file($file_path);

				}
				
				$cnt = $cnt + 1;
			}
		}
	} else {
		if ($debug) {echo "File > 1GB: " . $file_path . "\n";}

	}
}

echo count($local_files) . " are local\n";
echo count($remote_files) . " are on EC2\n";
echo "$cnt files need to be uploaded to by in sync\n";


// helper functions
function find_all_files($dir) 
{ 
    $result = array();
    if ($dir == '/Volumes/My Pictures/complete') {return $result;}
    $root = scandir($dir); 
    foreach($root as $value) 
    { 
        if($value === '.' || $value === '..') {continue;} 
        if(is_file("$dir/$value")) {$result["$dir/$value"]=1;continue;} 
        
        $flist = find_all_files("$dir/$value");
        if (!empty($flist)) {
        	foreach($flist as $value => $j) 
        	{ 
           	 	$result[$value]=1; 
        	} 
        }
    } 
    return $result; 
} 

?>
