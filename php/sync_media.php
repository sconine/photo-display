<?php
// Get the media we have stored on S3 and load it into a dynamoDB

require 'vendor/autoload.php';

// don't want to print debug through web server in general
$debug = false; 
if (!isset($_SERVER['HTTP_HOST'])) {
    $debug = true; 
} else {
    if (isset($_REQUEST['debug'])) {$debug = true;}
}

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
        if ($debug) {echo "Found Table: " . $table_name . "<br>\n";}
    }
}

// Create table is non-existent
if (!$has_table ) {
    // This can take a few mintes so increase timelimit
    set_time_limit(600);
    
    if ($debug) {echo "Attempting to Create Table: media_files<br>\n";}
    $client->createTable(array(
        'TableName' => 'media_regions',
        'AttributeDefinitions' => array(
            array(
                'AttributeName' => 'file_name',
                'AttributeType' => 'S'
            )
        ),
        'KeySchema' => array(
            array(
                'AttributeName' => 'file_name',
                'KeyType'       => 'HASH'
            )
        ),
        'ProvisionedThroughput' => array(
            'ReadCapacityUnits'  => 10,
            'WriteCapacityUnits' => 20
        )
    ));
    if ($debug) {echo "Created Table: media_files<br>\n";}
    $client->waitUntilTableExists(array('TableName' => 'media_files'));
    if ($debug) {echo "Table Exists!<br>\n";}

}

// Likely field list:
// 'file_name','shown_state','shown_on', 'file_name','shown_state',











?>
