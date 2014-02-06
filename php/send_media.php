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

if ($debug) {echo "datastring: $datastring\n";}
if ($debug) {var_dump($config);}

// Connect to amazon storage
require '../vendor/autoload.php';
use Aws\Common\Aws;

// You'll need to edit this with your config
$aws = Aws::factory('/usr/www/html/photo-display/php/amazon_config.json');
$client = $aws->get('s3');

// Set the bucket for where media is stored
$bucket = $config['ec2_image_bucket'];
if (isset($_REQUEST['media_id'])) {
    // Get an object using the getObject operation
    $result = $client->getObject(array(
        'Bucket' => $bucket,
        'Key'    => $_REQUEST['media_id']
    ));

    // Deal with consol calls vs calls through a web server
    if (isset($_SERVER['HTTP_HOST'])) {
        $f_ext = strtolower(substr($_REQUEST['media_id'], -3));
        if ($f_ext == 'mov') {
            header('Content-type: video/quicktime');
        } elseif ($f_ext == 'gif') {
            header('Content-type: image/gif');
        } else {
            header('Content-type: image/jpg');
        }
        echo $result['Body'];
    } else {
        echo "Got the body, but did not stream to concole\n";
    }
}



// Code to see buckets (useful for debug)
//$result = $client->listBuckets();
//foreach ($result['Buckets'] as $bucket) {
    // Each Bucket value will contain a Name and CreationDate
    //echo "{$bucket['Name']} - {$bucket['CreationDate']}\n";
//}


?>
