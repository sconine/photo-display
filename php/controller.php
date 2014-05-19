<?php
// A script that lets you control settings for each screen

// Load my configuration
require '../vendor/autoload.php';
$datastring = file_get_contents('../master_config.json');
$config = json_decode($datastring, true);

date_default_timezone_set('America/New_York');

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
use Aws\Common\Aws;
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
<body style="background-color: #FFFFFF;">
<div id="main">
	<div id="content">

<h1>Screens and Screen Groups</h1>
<hr>
<table style="margin-left: 20px;>
<tr>
    <th>Type</th>
    <th>Name</td>
    <th>Region</td>
    <th>Speed</th>
    <th>Active</th>
    <th>Screen Group</th>
    <th>Last Checkin</th>
    <th>Image History</th>
    <th>Storage Available</th>
    <th>Local IP</th>
    <th>Public IP</th>
</tr>

<?php

// Return every screen we know about in a json object
$iterator = $client->getIterator('Scan', array('TableName' => 'media_screens'));
$to_ret = array();
$alt = '';
foreach ($iterator as $item) {
    echo '<tr ' . $alt . '><td>';
    echo isset($item['screen_type']['S']) ? $item['screen_type']['S'] : 'Screen';
    echo '</td><td>';
    echo isset($item['screen_id']['S']) ? $item['screen_id']['S'] : 'Un-named';
    echo '</td><td>';
    echo isset($item['screen_region_name']['S']) ? $item['screen_region_name']['S'] : 'Unassigned';
    echo '</td><td>';
    echo isset($item['setting_change_speed']['N']) ? $item['setting_change_speed']['N'] : '8';
    echo '</td><td>';
    echo isset($item['screen_active']['S']) ? $item['screen_active']['N'] : '1';
    echo '</td><td>';
    echo isset($item['screen_group']['S']) ? $item['screen_group']['S'] : '-';
    echo '</td><td>';
    echo isset($item['screen_last_checkin']['N']) ? date("F j, Y, g:i a", $item['screen_last_checkin']['N']) : 'Unknown';
    echo '</td><td>';
    echo 'History Goes Here';
    echo '</td><td>';
    echo isset($item['screen_storage']['N']) ? $item['screen_storage']['N'] : 'Unknown';
    echo '</td><td>';
    echo isset($item['screen_private_ip']['S']) ? $item['screen_private_ip']['S'] : 'Screen';
    echo '</td><td>';
    echo isset($item['screen_public_ip']['S']) ? $item['screen_public_ip']['S'] : 'Screen';
    echo '</td></tr>';
    
    if ($alt == '') {$alt = ' class="alt" ';} 
    else {$alt = '';}
    
}
echo '</table>';


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


