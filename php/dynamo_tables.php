<?php

$result = $client->listTables();

// TableNames contains an array of table names
$has_regions = false;
$has_screens = false;
foreach ($result['TableNames'] as $table_name) {
    if ($table_name == "media_regions") {$has_regions = true;}
    if ($table_name == "media_screens") {$has_screens = true;}
    if ($debug) {echo "Found Table: " . $table_name . "<br>\n";}
}


// Create tables if non-existent
if (!$has_regions ) {
    // This can take a few mintes so increase timelimit
    set_time_limit(600);
    
    if ($debug) {echo "Attempting to Create Table: media_regions<br>\n";}
    $client->createTable(array(
        'TableName' => 'media_regions',
        'AttributeDefinitions' => array(
            array(
                'AttributeName' => 'region_name',
                'AttributeType' => 'S'
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
    if ($debug) {echo "Created Table: media_regions<br>\n";}
    $client->waitUntilTableExists(array('TableName' => 'media_regions'));
    if ($debug) {echo "Table Exists!<br>\n";}
}


// Create tables if non-existent
if (!$has_screens ) {
    // This can take a few mintes so increase timelimit
    set_time_limit(600);
    
    if ($debug) {echo "Attempting to Create Table: media_screens<br>\n";}
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
    if ($debug) {echo "Created Table: media_screens<br>\n";}
    $client->waitUntilTableExists(array('TableName' => 'media_screens'));
    if ($debug) {echo "Table Exists!<br>\n";}
}

?>
