<?php
// A script that lets you control settings for each screen

// Load my configuration
require '../vendor/autoload.php';
$datastring = file_get_contents('../master_config.json');
$config = json_decode($datastring, true);

// don't want to print debug through web server in general
$debug = false; 
if (!isset($_SERVER['HTTP_HOST'])) {
    $debug = true; 
} else {
    if (isset($_REQUEST['debug'])) {$debug = true;}
}

//Use MY SQL - this include assumes that $config has been loaded 
include 'my_sql.php';

// You'll need to edit this with your config file
// make sure you specify the correct region as dynamo is region specific
$aws = Aws::factory('/usr/www/html/photo-display/php/amazon_config.json');
$client = $aws->get('DynamoDb');

// Make sure the dynamo tables exists assumes $client is defined
include 'dynamo_tables.php';
?>
<html>
<head><Title>Media Display Controller</title>
<script src="//code.jquery.com/jquery-1.10.2.min.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="../node/public/master_style.css">

</head>
<body>
<div id="main">
	<div id="content">

Screens and Screen Groups:<br>
<table>
<tr>
    <td>Type</td>
    <td>Name</td>
    <td>Speed</td>
    <td>Show</td>
    <td>Screen Group</td>
    <td>Last Checkin</td>
    <td>Image History</td>
    <td>Storage Available</td>
    <td>Local IP</td>
    <td>Public IP</td>
</tr>

<?php

// Return every screen we know about in a json object
$iterator = $client->getIterator('Scan', array('TableName' => 'media_screens'));
$to_ret = array();
foreach ($iterator as $item) {
    echo '<tr><td>';
    echo isset($item['screen_type']['S']) ? $item['screen_type']['S'] : 'Screen';
    echo '</td><td>';
    echo isset($item['screen_type']['S']) ? $item['screen_type']['S'] : 'Screen';
    echo '</td><td>';
    echo isset($item['screen_type']['S']) ? $item['screen_type']['S'] : 'Screen';
    echo '</td><td>';
    echo isset($item['screen_type']['S']) ? $item['screen_type']['S'] : 'Screen';
    echo '</td><td>';
    echo isset($item['screen_type']['S']) ? $item['screen_type']['S'] : 'Screen';
    echo '</td><td>';
    echo isset($item['screen_type']['S']) ? $item['screen_type']['S'] : 'Screen';
    echo '</td><td>';
    
    
    $ta['screen_id'] = $item['screen_id']['S'];
    $ta['screen_region_name'] = $item['screen_region_name']['S'];
    $ta['screen_private_ip'] = $item['screen_private_ip']['S'];
    $ta['screen_public_ip'] = $item['screen_public_ip']['S'];
    $ta['screen_active'] = $item['screen_active']['N'];
    $ta['screen_settings']['change_speed'] = isset($item['setting_change_speed']['N']) ? $item['setting_change_speed']['N'] : 8;
    $ta['screen_settings']['movie_override_speed'] = isset($item['setting_movie_override_speed']['N']) ? $item['setting_movie_override_speed']['N'] : true;
    $to_ret[] = $ta;
}


// Need to display screens and screen groups
// Field List: Type, Name, Speed, Show, Screen Group, Last Checkin, Image History, Storage Available, Local IP, Public IP

// Settings panel for a screen should let you modify:
// Speed
// Movies Over-ride Speed
// Show: (o) All Available
//       (o) This folder and Below [    ] GO
//       (o) These images [    ] Browse GO
// Put in Screen Group [         ] GO
// (this moves control to the screen group level)


?>


	</div>
</div>
</body>
</html>


