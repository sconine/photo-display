<?php
// A script that registers screens and returns peers
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
$aws = Aws::factory('/usr/www/html/photo-display/php/amazon_config.json');
$client = $aws->get('DynamoDb');

// Make sure the dynamo tables exists assumes $client is defined
include 'dynamo_tables.php';

// ok we've got tables, see what we were sent
if ($debug) {echo "Current Tables Exist<br>\n";}
$created_region = false;
$region_name = '';
$screen_id = '';
$screen_private_ip = '192.168.1.77';
$screen_public_ip = '192.168.1.66';
$screen_storage = 0;
if (isset($_REQUEST['screen_region_name'])) {$region_name = $_REQUEST['screen_region_name'];}
if ($region_name == '') {$region_name = 'Default Region';}
if (isset($_REQUEST['screen_id'])) {$screen_id = $_REQUEST['screen_id'];}
if ($screen_id == '') {$screen_id = 'Default Screen';}
if (isset($_REQUEST['screen_private_ip'])) {$screen_private_ip = $_REQUEST['screen_private_ip'];}
if (isset($_REQUEST['screen_storage'])) {$screen_storage = $_REQUEST['screen_storage'];}
if (isset($_REQUEST['screen_public_ip'])) {$screen_public_ip = $_REQUEST['screen_public_ip'];}
$time = time();

// have we seen this region
if ($debug) {echo "Looking up region: $region_name<br>\n";}
$result = $client->getItem(array(
    'ConsistentRead' => true,
    'TableName' => 'media_regions',
    'Key'       => array(
        'region_name'   => array('S' => $region_name)
    )
));
if ($debug) {var_dump($result); echo '<br>';}

if (!isset($result['Item']['region_name']['S'])) {
    // Add this region
    if ($debug) {echo "$region_name not found, adding region now<br>\n";}
    $result = $client->putItem(array(
        'TableName' => 'media_regions',
        'Item' => $client->formatAttributes(array(
            'region_name'      => $region_name,
            'region_active'    => true,
            'region_screen_list'   => array($screen_id)
        )),
        'ReturnConsumedCapacity' => 'TOTAL'
    ));
    $created_region = true;
    if ($debug) {echo "$region_name added<br>\n";}
} else {
    if ($debug) {echo "$region_name found!<br>\n";}
}

// have we seen this screen
if ($debug) {echo "Looking up screen: $screen_id in $region_name<br>\n";}
$result = $client->getItem(array(
    'ConsistentRead' => true,
    'TableName' => 'media_screens',
    'Key'       => array(
        'screen_id'   => array('S' => $screen_id),
        'screen_region_name'   => array('S' => $region_name)
    )
));
if ($debug) {var_dump($result); echo '<br>';}

if (!isset($result['Item']['screen_id']['S'])) {
    // Add this screen
    if ($debug) {echo "$screen_id in $region_name not found, adding screen now<br>\n";}
    $result = $client->putItem(array(
        'TableName' => 'media_screens',
        'Item' => $client->formatAttributes(array(
            'screen_id'      => $screen_id,
            'screen_region_name'    => $region_name,
            'screen_private_ip'    => $screen_private_ip,
            'screen_public_ip'    => $screen_public_ip,
            'screen_last_checkin'    => $time,
            'screen_active'    => 1,
            'setting_change_speed' => 5,
            'setting_movie_override_speed' => 1,
            'screen_storage' => $screen_storage
        )),
        'ReturnConsumedCapacity' => 'TOTAL'
    ));
     if ($debug) {echo "$screen_id in $region_name added<br>\n";}
   
    // Make sure to push this screen onto the region screen list if we didn't just create the region
    if (!$created_region) {
        if ($debug) {echo "$screen_id in $region_name adding to region list<br>\n";}
        $result = $client->updateItem(array(
            'TableName' => 'media_regions',
            'Key'       => array(
                'region_name'   => array('S' => $region_name)
            ),
            'AttributeUpdates' => array(
                'region_screen_list'   => array('Action' => 'ADD', 'Value' => array('SS' => array($screen_id)))
            )
        ));
        if ($debug) {echo "$screen_id in $region_name pushed onto region list<br>\n";}
    }

} else {
    // Update the screen_last_checkin and IP values for this screen
    if ($debug) {echo "$screen_id in $region_name found!<br>\n";}
    $result = $client->updateItem(array(
        'TableName' => 'media_screens',
        'Key'       => array(
            'screen_id'   => array('S' => $screen_id),
            'screen_region_name'   => array('S' => $region_name)
        ),
        'AttributeUpdates' => array(
            'screen_private_ip'    =>  array('Action' => 'PUT', 'Value' => array('S' => $screen_private_ip)),
            'screen_public_ip'    =>  array('Action' => 'PUT', 'Value' => array('S' => $screen_public_ip)),
            'screen_last_checkin'    =>  array('Action' => 'PUT', 'Value' => array('N' => $time)),
            'screen_storage'    =>  array('Action' => 'PUT', 'Value' => array('N' => $screen_storage))
        )
    ));    
    if ($debug) {echo "$screen_id in $region_name updated<br>\n";}

}


// Finally return every screen we know about in a json object
// (could filter this by region, but not expecting this to be very many overall)
$iterator = $client->getIterator('Scan', array('TableName' => 'media_screens'));
$to_ret = array();
foreach ($iterator as $item) {
    $ta = array();
    $ta['screen_id'] = $item['screen_id']['S'];
    $ta['screen_region_name'] = $item['screen_region_name']['S'];
    $ta['screen_private_ip'] = $item['screen_private_ip']['S'];
    $ta['screen_public_ip'] = $item['screen_public_ip']['S'];
    $ta['screen_active'] = $item['screen_active']['N'];
    $ta['screen_storage'] = isset($item['screen_storage']['N']) ? $item['screen_storage']['N'] : 0;
    $ta['screen_settings']['change_speed'] = isset($item['setting_change_speed']['N']) ? $item['setting_change_speed']['N'] : 8;
    $ta['screen_settings']['movie_override_speed'] = isset($item['setting_movie_override_speed']['N']) ? $item['setting_movie_override_speed']['N'] : true;
    $to_ret[] = $ta;
}


//if ($debug) {echo '<hr>'; var_dump($to_ret);}
echo json_encode($to_ret);



?>
