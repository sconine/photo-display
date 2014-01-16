<?php
// This is currently a sample script that I'm using to understand communication
// with S3 and Amazon in general

require 'vendor/autoload.php';

use Aws\Common\Aws;

// You'll need to edit this with your config
$aws = Aws::factory('/usr/www/html/photo-display/php/amz_config.json');
$client = $aws->get('s3');

// Set the bucket for where media is stored
$bucket = 'SConine_Photos';
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

// Method to go into a specific bucket with a filter
//$bucket = 'SConine_Photos';
//$iterator = $client->getIterator('ListObjects', array(
//    'Bucket' => $bucket,
//    'Prefix' => 'Dec-2005'
//));

//foreach ($iterator as $object) {
    //echo $object['Key'] . "\n";
//}

?>
