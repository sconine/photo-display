<?php
require 'vendor/autoload.php';

use Aws\Common\Aws;

// You'll need ot edit this with your config
$aws = Aws::factory('/usr/www/html/photo-display/amz_config.json');
$client = $aws->get('s3');

//exit;

$bucket = 'MyTestPHP';
try {
        $result = $client->createBucket(array('Bucket' => $bucket));
} catch (Aws\S3\Exception\S3Exception $e) {
    echo $e->getMessage();
}

var_dump($result);

?>
