<?php
// A script that returns a list of media that a specific screen should display
// for reference: http://docs.aws.amazon.com/aws-sdk-php/guide/latest/service-dynamodb.html
// and also useful: http://docs.aws.amazon.com/amazondynamodb/latest/developerguide/AppendixSampleDataCodePHP.html

require '../vendor/autoload.php';

// don't want to print debug through web server in general
$debug = false; 
if (!isset($_SERVER['HTTP_HOST'])) {
    $debug = true; 
} else {
    if (isset($_REQUEST['debug'])) {$debug = true;}
}

use Aws\Common\Aws;

// You'll need to edit this with your config file
// make sure you specify the correct region as dynamo is region specific
$aws = Aws::factory('/usr/www/html/photo-display/php/amz_config.json');
$client = $aws->get('DynamoDb');

//TODO: Query media tables and return list of media
// Finally return every screen we know about in a json object
// (could filter this by region, but not expecting this to be very many overall)
$iterator = $client->getIterator('Scan', array('TableName' => 'media'));
$to_ret = array();
foreach ($iterator as $item) {
    $ta = array();
    $ta['screen_id'] = $item['screen_id']['S'];
    $ta['screen_region_name'] = $item['screen_region_name']['S'];
    $ta['screen_private_ip'] = $item['screen_private_ip']['S'];
    $ta['screen_public_ip'] = $item['screen_public_ip']['S'];
    $ta['screen_active'] = $item['screen_active']['N'];
    $to_ret[] = $ta;
}


//if ($debug) {echo '<hr>'; var_dump($to_ret);}
echo json_encode($to_ret);






?>
