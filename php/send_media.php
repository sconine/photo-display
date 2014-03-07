<?php
// Script to send a specific media file to a screen
// don't want to print debug through web server in general
$debug = false; 
if (!isset($_SERVER['HTTP_HOST'])) {
    $debug = true; 
} else {
    if (isset($_REQUEST['debug'])) {$debug = true;}
}
// Load my configuration
$datastring = file_get_contents('../master_config.json');
$config = json_decode($datastring, true);
include '/usr/www/html/photo-display/php/curl_functions.php';
//Use MY SQL - this include assumes that $config has been loaded 
include 'my_sql.php';

// Check that we've got a valid token
if (isset($_REQUEST['enc'])) {check_token($_REQUEST['enc'], $mysqli);}
else {echo 'no token passed'; exit;}

if ($debug) {echo "datastring: $datastring\n";}
if ($debug) {var_dump($config);}

// Set an unlimited memory limit so that big files can be sent
ini_set('memory_limit', '-1');

// Connect to amazon storage
require '../vendor/autoload.php';
use Aws\Common\Aws;

// You'll need to edit this with your config
$aws = Aws::factory('/usr/www/html/photo-display/php/amazon_config.json');
$client = $aws->get('s3');

// Set the bucket for where media is stored
//TODO: Figure out how to get large movie files as the
// approach below doesn't work and gives this error in /var/log/nginx/error.log
// *1953 recv() failed (104: Connection reset by peer) while reading response header from upstream, client: 24.147.168.202,
$bucket = $config['ec2_image_bucket'];
if (isset($_REQUEST['media_path'])) {
    // Get an object using the getObject operation
    $result = $client->getObject(array(
        'Bucket' => $bucket,
        'Key'    => $_REQUEST['media_path']
    ));
    
    //TODO: Get the md5 and sha1 meta data and send that back for comparison and storage
    // Enforce a 1GB limit here too
    if ($result['Size'] < 1000000000) {
        // Deal with consol calls vs calls through a web server
        if (isset($_SERVER['HTTP_HOST'])) {
            $f_ext = strtolower(substr($_REQUEST['media_path'], -3));
            if ($f_ext == 'mov') {
                header('Content-type: video/quicktime');
            } elseif ($f_ext == 'mp4') {
                header('Content-type: video/mp4');
           } elseif ($f_ext == 'gif') {
                header('Content-type: image/gif');
            } else {
                header('Content-type: image/jpg');
            }
            echo $result['Body'];
        } else {
            echo "Got the body, but did not stream to concole\n";
        }
    } else {
        header("HTTP/1.1 500 Internal Server Error");
    }
}



// Code to see buckets (useful for debug)
//$result = $client->listBuckets();
//foreach ($result['Buckets'] as $bucket) {
    // Each Bucket value will contain a Name and CreationDate
    //echo "{$bucket['Name']} - {$bucket['CreationDate']}\n";
//}


?>
