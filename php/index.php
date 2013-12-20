<?php
// This is currently a sample script that I'm using to understand communication
// with S3 and Amazon in general

require 'vendor/autoload.php';

use Aws\Common\Aws;

// You'll need ot edit this with your config
$aws = Aws::factory('/usr/www/html/photo-display/amz_config.json');
$client = $aws->get('s3');

$result = $client->listBuckets();

foreach ($result['Buckets'] as $bucket) {
    // Each Bucket value will contain a Name and CreationDate
    //echo "{$bucket['Name']} - {$bucket['CreationDate']}\n";
}

$bucket = 'SConine_Photos';

$iterator = $client->getIterator('ListObjects', array(
    'Bucket' => $bucket,
    'Prefix' => 'Dec-2005'
));

foreach ($iterator as $object) {
    //echo $object['Key'] . "\n";
}

// Get an object using the getObject operation
$result = $client->getObject(array(
    'Bucket' => $bucket,
    'Key'    => 'Oct-2012/DSCN2599.MOV'
));


if (isset($_SERVER['HTTP_HOST'])) {
    // This works sending to Chrome
    header('Content-type: video/quicktime');
    echo $result['Body'];
} else {
    echo "Got the body, but did not stream to concole\n";
}


?>
