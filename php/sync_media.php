<?php
// Get the media we have stored on S3 and load it into a dynamoDB

require 'vendor/autoload.php';

use Aws\Common\Aws;

// You'll need to edit this with your config
$aws = Aws::factory('/usr/www/html/photo-display/php/amz_config.json');
$client = $aws->get('DynamoDb');
$result = $client->listTables();

// TableNames contains an array of table names
$has_table = false;
foreach ($result['TableNames'] as $table_name) {
    if ($table_name == "media_files") {$has_table = true;}
    if (!if (isset($_SERVER['HTTP_HOST'])) {
        echo $$table_name . "\n";
    }
}

// Create table is non-existent
if (!$has_table ) {
    $client->createTable(array(
    'TableName' => 'media_files',
    'AttributeDefinitions' => array(
        array(
            'AttributeName' => 'file_name',
            'AttributeType' => 'S'
        ),
        array(
            'AttributeName' => 'shown_state',
            'AttributeType' => 'N'
        ),
        array(
            'AttributeName' => 'shown_on',
            'AttributeType' => 'SS'
        )
    ),
    'KeySchema' => array(
        array(
            'AttributeName' => 'file_name',
            'KeyType'       => 'HASH'
        ),
        array(
            'AttributeName' => 'shown_state',
            'KeyType'       => 'RANGE'
        )
    ),
    'ProvisionedThroughput' => array(
        'ReadCapacityUnits'  => 10,
        'WriteCapacityUnits' => 20
    )
));
    
}


?>
