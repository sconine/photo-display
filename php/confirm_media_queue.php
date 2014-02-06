<?php
// A script that screens call to confirm the received and registered media
// don't want to print debug through web server in general
$debug = true; 
if (!isset($_SERVER['HTTP_HOST'])) {
    $debug = true; 
} else {
    if (isset($_REQUEST['debug'])) {$debug = true;}
}

// Load my configuration
$datastring = file_get_contents('../master_config.json');
$config = json_decode($datastring, true);

if ($debug) {echo "datastring: $datastring\n";}
if ($debug) {var_dump($config);}

//Use MY SQL - this include assumes that $config has been loaded 
include 'my_sql.php';

// Call to this script looks like
//$post_data = "medialist=" & urlencode(json_encode($confirm_reg));
//$url = 'http://' . $config['master_server'] . '/confirm_media_queue.php?'
//  . '&screen_id=' . $config['screen_id'] 
//  . '&region=' . $config['region'];
  
// Always reset things that were sent but not congfirmed
$media_list = json_decode($_REQUEST['medialist']);
$usql = '';
foreach ($media_list as $i=>$media_path) {
  if ($usql != '') {$usql .= ',';}
  $usql .= sqlq($media_path,0);
}

$sql = 'UPDATE media_files SET shown=1 WHERE media_path IN (' . $usql . ');';
if ($debug) {echo "Running: $sql\n";}
if (!$mysqli->query($sql)) {die("Insert Failed: (" . $mysqli->errno . ") " . $mysqli->error);}


echo 'success!';
?>
