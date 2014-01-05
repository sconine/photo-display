<?php
// A php script that will run on a local photo display and talk to the main EC2 server, 
// retreive media to the local network and enqueue media for display. 

// Load my configuration
$datastring = file_get_contents('config.json');
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
$link = mysql_connect('localhost', 'mysql_user', 'mysql_password') or die('Could not connect: ' . mysql_error());
echo 'Connected to MySQL';
mysql_select_db('my_database') or die('Could not select database');

/////////////////////////////////////////////////
// Register yourself and learn about local peers
// Build the my_peers table schema on the fly
$sql = 'CREATE TABLE IF NOT EXISTS my_peers (region varchar(128) NOT NULL, screen_id varchar(128) NOT NULL, private_ip varchar(32) NOT NULL, public_ip varchar(32) NOT NULL, PRIMARY KEY (region, screen_id));';
$result = mysql_query($sql, $link);
if (!$result) {die('Invalid query: ' . mysql_error() . "\n");}

// Call the central public registration server
$url = 'http://MyEC2instance.com/find_peers.php?private_ip='
  . $_SERVER['SERVER_ADDR'] 
  . '&screen_id=' . $config['screen_id'] 
  . '&public_ip=' . $config['public_ip'] 
  . '&region=' . $config['region'];
  
$ch = curl_init();
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_URL,$url);
$result=curl_exec($ch);
if(curl_errno($c)) {
    echo 'error:' . curl_error($c);
} else {
  $my_peers = json_decode($result, true);
  
  // Lookup peers we already know about in our local database
  $sql = "SELECT private_ip , screen_id , region, public_ip FROM my_peers";
  $known_peers = query_to_array($sql, &$link) 
  
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
}

// Finally pull back peers in same region
$sql = "SELECT private_ip, screen_id FROM my_peers WHERE region=" . sqlq($config['region'], 0);
$local_peers = query_to_array($sql, &$link) 
// End Self registration and awareness
/////////////////////////////////////////////////

/////////////////////////////////////////////////
// Now retreive what we're suppose to show next
// Build the my_peers table schema on the fly
$sql = 'CREATE TABLE IF NOT EXISTS my_media ('
  . 'display_order int NOT NULL AUTO_INCREMENT, '
  . 'media_path varchar(1024) NOT NULL, '
  . 'media_type varchar(128) NOT NULL, ' 
  . 'media_host varchar(64) NULL, ' 
  . 'displayed datetime NULL, '
  . 'PRIMARY KEY (display_order));';
$result = mysql_query($sql, $link);
if (!$result) {die('Invalid query: ' . mysql_error() . "\n");}

// This returns the next 'X' files that this screen will display
$url = 'http://MyEC2instance.com/send_media_queue.php?'
  . '&screen_id=' . $config['screen_id'] 
  . '&region=' . $config['region'];

$confirm_reg = array();
$ch = curl_init();
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_URL,$url);
$result=curl_exec($ch);
if(curl_errno($c)) {
    echo 'error:' . curl_error($c);
} else {
  $my_media = json_decode($result, true);
  
  foreach ($my_media as $i=>$media) {
    // see if we have this locally
    $filepath = $config . $media['media_path'];
    if (file_exists($filepath)) {
      $confirm_reg[] = $media['media_path'];
    } else {
      // see if a peer has it
      
      
    }
    
  }

}


//$sql = 'CREATE TABLE IF NOT EXISTS my_peers (region varchar(128) NOT NULL, Shown bit NOT NULL, PositionNum INT, LastPosition INT);';










$arr = array('cnt' => $_REQUEST['cnt'], 'duration' => $_REQUEST['duration']);

echo json_encode($arr);




// Close MySQL Connection
mysql_close($link);




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
