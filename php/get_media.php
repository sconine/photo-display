<?php
// A php script that will run on a local photo display and talk to the main EC2 server, 
// retreive media to the local network and enqueue media for display. 

// Load my configuration
$datastring = file_get_contents('../config.json');
$config = json_decode($datastring, true);

// Die if already running
$my_name = $_SERVER['SCRIPT_NAME'];
exec("ps -C $my_name -o pid=",$pids);
if (count($pids) > 1) {
  echo "Already Running $my_name:";
  var_dump($pids);
  exit();
}

// Connect to local MySQL database
$link = mysql_connect($config['mysql']['host'], $config['mysql']['user'], $config['mysql']['password']) or die('Could not connect: ' . mysql_error());
echo 'Connected to MySQL';
mysql_select_db($config['mysql']['database']) or die('Could not select database');

/////////////////////////////////////////////////
// Register yourself and learn about local peers
// Build the my_peers table schema on the fly
$sql = 'CREATE TABLE IF NOT EXISTS my_peers (region varchar(128) NOT NULL, screen_id varchar(128) NOT NULL, private_ip varchar(32) NOT NULL, public_ip varchar(32) NOT NULL, PRIMARY KEY (region, screen_id));';
$result = mysql_query($sql, $link);
if (!$result) {die('Invalid query: ' . mysql_error() . "\n");}

// Call the central public registration server
$url = 'http://' . $config['master_server'] . '/find_peers.php?private_ip='
  . $_SERVER['SERVER_ADDR'] 
  . '&screen_id=' . $config['screen_id'] 
  . '&public_ip=' . $config['public_ip'] 
  . '&region=' . $config['region'];
$my_peers = curl_get_array($url);

// Lookup peers we already know about in our local database
$sql = "SELECT private_ip , screen_id , region, public_ip FROM my_peers";
$known_peers = query_to_array($sql, &$link);

foreach ($my_peers as $i=>$peer) {
  // do we know about this peer (yea loop within a loop... not expecting more than 100 peers)
  $known_peer = false;
  foreach ($known_peers as $j=$k_peer) {
    if ($peer['region'] == $k_peer['region'] && $peer['screen_id'] == $k_peer['screen_id']){
      $known_peer = true;
      
      // Did other info change, if so update in the local database
      if ($peer['private_ip'] != $k_peer['private_ip'] || $peer['public_ip'] != $k_peer['public_ip']) {
          $sql = "UPDATE my_peers SET private_ip =" . sqlq($peer['private_ip'], 0) . ", public_ip =" . sqlq($peer['public_ip'], 0) . " WHERE screen_id = " . sqlq($peer['screen_id'], 0) . " AND region = " . sqlq($peer['region'], 0);
          $usql = query_to_array($sql, &$link) ;
      }
    }
    
    // If a new peer add them to the local db
    if (!$known_peer) {
      $sql = "INSERT INTO my_peers (private_ip , screen_id , region, public_ip) VALUES (";
      $sql .= sqlq($peer['private_ip'], 0) . ',';
      $sql .= sqlq($peer['screen_id'], 0) . ',';
      $sql .= sqlq($peer['region'], 0) . ',';
      $sql .= sqlq($peer['public_ip'], 0) . ')';
      $isql = query_to_array($sql, &$link) ;
    }
  }
}

// Finally pull back peers in same region
$sql = "SELECT private_ip, screen_id FROM my_peers WHERE region=" . sqlq($config['region'], 0);
$local_peers = query_to_array($sql, &$link);
// End Self registration and awareness
/////////////////////////////////////////////////

/////////////////////////////////////////////////
// Now retreive what we're suppose to show next
// Build the my_media table schema on the fly
$sql = 'CREATE TABLE IF NOT EXISTS my_media ('
  . 'display_order int NOT NULL AUTO_INCREMENT, '
  . 'media_path varchar(1024) NOT NULL, '
  . 'media_type varchar(128) NOT NULL, ' 
  . 'media_size int NOT NULL, ' 
  . 'media_host varchar(64) NULL, ' 
  . 'displayed datetime NULL, '
  . 'PRIMARY KEY (display_order));';
$result = mysql_query($sql, $link);
if (!$result) {die('Invalid query: ' . mysql_error() . "\n");}

// Do a little disk space management (if < 100MB)
if (disk_free_space($config['media_folder']) < (1024 * 1024 * 100)) {
  //remove most recently displayed files
  $sql = "SELECT display_order, media_path, media_size FROM my_media WHERE displayed is not null AND media_host = 'localhost' ORDER BY displayed desc limit 200";
  $remove_files = query_to_array($sql, &$link);
  
  $tot_size = 0;
  foreach ($remove_files as $i=>$row) {
    if ($tot_size > 1024 * 1024 * 100) {
      break;
    } 
    unlink($config['media_folder'] . $row['media_path']);
    // not going to do this for now, as it will be interesting to keep 
    // an ongoing log of what was shown when
    // $sql = "DELETE FROM my_media WHERE display_order=" . sqlq($row['display_order']), 1);
    // $dsql = query_to_array($sql, &$link);
    $tot_size = $tot_size + $row['media_size'];
  }
  
}


// This returns the next 'X' files that this screen will display
$url = 'http://' . $config['master_server'] . '/send_media_queue.php?'
  . '&screen_id=' . $config['screen_id'] 
  . '&region=' . $config['region'];

$confirm_reg = array();
$isql = "";
$my_media = curl_get_array($url);
foreach ($my_media as $i=>$media) {
  // see if we have this locally
  $media_host = '';
  $filepath = $config['media_folder'] . $media['media_path'];
  if (file_exists($filepath)) {
    $confirm_reg[] = $media['media_path'];
    $media_host = 'localhost';
  } else {
    // see if a local peer has it
    foreach ($local_peers as $j => $peer) {
      $url = 'http://' . $peer['private_ip'] . ':8080/find_media?media_path=' . urlencode($media['media_path']);
      $peer_media = curl_get_array($url);
      
      if ($peer_media[0]['found']) {
        $confirm_reg[] = $media['media_path'];
        $media_host = $peer['private_ip'];
        break;
      }
    }
  }
  
  // Did we find locally or do we need to retreive it
  if ($media_host == '') {
    //TODO: add disk space checks/cleanup and check file size prior to downloading
    $url = 'http://' . $config['master_server'] . '/send_media.php?media_path=' . urlencode($media['media_path']);
    set_time_limit(0);
    $fp = fopen ($filepath, 'w+');
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 50);
    curl_setopt($ch, CURLOPT_FILE, $fp); 
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_exec($ch); 
    curl_close($ch);
    fclose($fp);
    if(curl_errno($c)) {
      echo 'error:' . curl_error($c);
    } else {
      $confirm_reg[] = $media['media_path'];
      $media_host = 'localhost';    
    }
  }
  
  if ($media_host != '') {
    $isql .= "INSERT INTO my_media (media_path, media_type, media_size, media_host) VALUES ("
          . sqlq($media['media_path'], 0) . ','
          . sqlq($media['media_type'], 0) . ','
          . sqlq($media['media_size'], 1) . ','
          . sqlq($media_host, 0) . '); ';
  }
}

// Finally commit what we are going to show to the database and register it with the main host
$post_data = "medialist=" & urlencode(json_encode($confirm_reg));
$url = 'http://' . $config['master_server'] . '/confirm_media_queue.php?'
  . '&screen_id=' . $config['screen_id'] 
  . '&region=' . $config['region'];
$ch = curl_init();
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_URL,$url);
curl_setopt($ch, CURLOPT_POSTFIELDS,  $post_data);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_POST, 1);
$result=curl_exec($ch);
if(curl_errno($c)) {
  echo 'error will not update local database:' . curl_error($c);
} else {
  $misql = query_to_array($isql, &$link) ;
}

// Close MySQL Connection
mysql_close($link);


// CURL Functions
function curl_get_array($url) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_URL,$url);
  $result=curl_exec($ch);
  if(curl_errno($c)) {
    echo 'error:' . curl_error($c);
  } else {
    return json_decode($result, true);
  }
  return array();
}

// Query helper functions
function sqlq($var, $var_type) {
  if ($var_type == 1) {
    if (is_numeric($var) && !empty($var)) {
      return $var;
    } 
  } else {
    if (!empty($var)) {
      $var = str_replace("'", "''", $var);
      return "'" . $var . "'";
    }
  }
  return 'NULL';
}

function query_to_array($sql, &$link) {
  var $to_ret = array();
  $result = mysql_query($sql, $link);
  while ($row = mysql_fetch_assoc($result)) {
      $to_ret[] = $row;
  }
  mysql_free_result($result);
  return $to_ret;
}
?>
