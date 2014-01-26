<?php
// A script that registers screens and returns peers
// for reference: http://docs.aws.amazon.com/aws-sdk-php/guide/latest/service-dynamodb.html

require '../vendor/autoload.php';

use Aws\Common\Aws;

// You'll need to edit this with your config
$aws = Aws::factory('/usr/www/html/photo-display/php/amz_config.json');
$client = $aws->get('DynamoDb');
$result = $client->listTables();

// TableNames contains an array of table names
$has_regions = false;
$has_screens = false;
foreach ($result['TableNames'] as $table_name) {
    if ($table_name == "media_regions") {$has_regions = true;}
    if ($table_name == "media_screens") {$has_screens = true;}
    if (!isset($_SERVER['HTTP_HOST'])) {
        echo $$table_name . "\n";
    }
}

// Create tables if non-existent
if (!$has_regions ) {
    $client->createTable(array(
        'TableName' => 'media_regions',
        'AttributeDefinitions' => array(
            array(
                'AttributeName' => 'region_name',
                'AttributeType' => 'S'
            ),
            array(
                'AttributeName' => 'region_active',
                'AttributeType' => 'B'
            ),
            array(
                'AttributeName' => 'region_screen_list',
                'AttributeType' => 'SS'
            )
        ),
        'KeySchema' => array(
            array(
                'AttributeName' => 'region_name',
                'KeyType'       => 'HASH'
            )
        ),
        'ProvisionedThroughput' => array(
            'ReadCapacityUnits'  => 1,
            'WriteCapacityUnits' => 1
        )
    ));
}

// Create tables if non-existent
if (!$has_screens ) {
    $client->createTable(array(
        'TableName' => 'media_screens',
        'AttributeDefinitions' => array(
            array(
                'AttributeName' => 'screen_id',
                'AttributeType' => 'S'
            ),
            array(
                'AttributeName' => 'screen_region_name',
                'AttributeType' => 'S'
            ),
            array(
                'AttributeName' => 'screen_private_ip',
                'AttributeType' => 'S'
            ),
            array(
                'AttributeName' => 'screen_public_ip',
                'AttributeType' => 'S'
            ),
            array(
                'AttributeName' => 'screen_last_checkin',
                'AttributeType' => 'N'
            ),
            array(
                'AttributeName' => 'screen_active',
                'AttributeType' => 'B'
            )
        ),
        'KeySchema' => array(
            array(
                'AttributeName' => 'screen_id',
                'KeyType'       => 'HASH'
            ),
            array(
                'AttributeName' => 'screen_region_name',
                'KeyType'       => 'RANGE'
            )

        ),
        'ProvisionedThroughput' => array(
            'ReadCapacityUnits'  => 1,
            'WriteCapacityUnits' => 1
        )
    ));
}


// ok we've got tables, see what we were sent
$created_region = false;
$region_name = '';
$screen_id = '';
$screen_private_ip = '';
$screen_public_ip = '';
if (isset($_REQUEST['region'])) {$region_name = $_REQUEST['region'];}
if ($region_name == '') {$region_name = 'Default Region';}
if (isset($_REQUEST['screen_id'])) {$screen_id = $_REQUEST['screen_id'];}
if ($screen_id == '') {$screen_id = 'Default Screen';}
if (isset($_REQUEST['private_ip'])) {$screen_private_ip = $_REQUEST['private_ip'];}
if (isset($_REQUEST['public_ip'])) {$screen_public_ip = $_REQUEST['public_ip'];}
$time = time();

// have we seen this region
$result = $client->getItem(array(
    'ConsistentRead' => true,
    'TableName' => 'region_name',
    'Key'       => array(
        'region_name'   => array('S' => $region_name)
    )
));

if (!isset($result['Item']['region_name']['S'])) {
    // Add this region
    $result = $client->putItem(array(
        'TableName' => 'region_name',
        'Item' => $client->formatAttributes(array(
            'region_name'      => $region_name,
            'region_active'    => true,
            'region_screen_list'   => array($screen_id)
        )),
        'ReturnConsumedCapacity' => 'TOTAL'
    ));
    $created_region = true;
}

// have we seen this screen
$result = $client->getItem(array(
    'ConsistentRead' => true,
    'TableName' => 'media_screens',
    'Key'       => array(
        'screen_id'   => array('S' => $screen_id),
        'screen_region_name'   => array('S' => $region_name)
    )
));

if (!isset($result['Item']['screen_id']['S'])) {
    // Add this screen
    $result = $client->putItem(array(
        'TableName' => 'media_screens',
        'Item' => $client->formatAttributes(array(
            'screen_id'      => $screen_id,
            'screen_region_name'    => $region_name,
            'screen_private_ip'    => $screen_private_ip,
            'screen_public_ip'    => $screen_public_ip,
            'screen_last_checkin'    => $time,
            'screen_active'    => true
        )),
        'ReturnConsumedCapacity' => 'TOTAL'
    ));
    
    // Make sure to push this screen onto the region screen list if we didn't just create the region
    if (!$created_region) {
        $result = $client->updateItem(array(
            'TableName' => 'region_name',
            'Key'       => array(
                'region_name'   => array('S' => $region_name)
            ),
            'AttributeUpdates' => array(
                'region_screen_list'   => array('SS' => array($screen_id)),
                'Action' => 'ADD'
            )
        ));
    }

} else {
    // Update the screen_last_checkin and IP values for this screen
    $result = $client->updateItem(array(
        'TableName' => 'media_screens',
        'Key'       => array(
            'screen_id'   => array('S' => $screen_id),
            'screen_region_name'   => array('S' => $region_name)
        ),
        'AttributeUpdates' => array(
            'screen_private_ip'    =>  array('S' => $screen_private_ip),
            'screen_public_ip'    =>  array('S' => $screen_public_ip),
            'screen_last_checkin'    =>  array('N' => $time),
            'Action' => 'PUT'
        )
    ));    
    
}


// Finally return every screen we know about 
// (could filter this by region, but not expecting this to be very many overall)
$iterator = $client->getIterator('Scan', array('TableName' => 'media_screens'));
echo json_encode($iterator);



?>
