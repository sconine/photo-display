<?php
// A script that registers screens and returns peers

require 'vendor/autoload.php';

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
    if (!if (isset($_SERVER['HTTP_HOST'])) {
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



?>
