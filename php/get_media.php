<?php
// A php script that will run on a local photo display and talk to the main EC2 server, 
// retreive media to the local network and enqueue media for display. 

// Load my configuration
$datastring = file_get_contents('/usr/www/html/photo-display/master_config.json');
$config = json_decode($datastring, true);
$debug = true;
$file_batch_size = 25;

// Die if already running
$my_name = $_SERVER['SCRIPT_NAME'];
$cmd = 'ps -C "php ' . $my_name . '" -o pid=';
if ($debug) {echo "$cmd\n";}
exec($cmd,$pids);
if ($debug) {var_dump($pids); echo "\n";}
if (count($pids) > 1) {
  echo "Already Running $my_name\n";
  exit();
}

// Get local machine IP address
$my_ip =  gethostbyname(trim(`hostname --all-ip-addresses`)); 

//Use MY SQL - this include assumes that $config has been loaded 
include '/usr/www/html/photo-display/php/my_sql.php';
include '/usr/www/html/photo-display/php/curl_functions.php';


/////////////////////////////////////////////////
// Register yourself and learn about local peers
// Build the my_settings table schema on the fly
$sql = 'CREATE TABLE IF NOT EXISTS my_settings (setting_name varchar(128) NOT NULL, setting_value varchar(128) NOT NULL, PRIMARY KEY (setting_name));';
if (!$mysqli->query($sql)) {die("Table creation failed: (" . $mysqli->errno . ") " . $mysqli->error);}
if ($debug) {echo 'my_settings table Exists'. "\n";}

// Build the my_peers table schema on the fly
$sql = 'CREATE TABLE IF NOT EXISTS my_peers (screen_region_name varchar(128) NOT NULL, screen_id varchar(128) NOT NULL, screen_private_ip varchar(32) NOT NULL, screen_public_ip varchar(32) NOT NULL, PRIMARY KEY (screen_region_name, screen_id));';
if (!$mysqli->query($sql)) {die("Table creation failed: (" . $mysqli->errno . ") " . $mysqli->error);}
if ($debug) {echo 'my_peers table Exists'. "\n";}

// see how much space we have and send that the the central server
$disk_free = disk_free_space($config['media_folder']);

// Call the central public registration server
$url = 'http://' . $config['master_server'] . '/photo-display/php/find_peers.php?screen_private_ip=' . $my_ip
  . '&screen_id=' . $config['screen_id'] 
  . '&screen_public_ip=' . $config['public_ip'] 
  . '&screen_storage=' . $disk_free 
  . '&screen_region_name=' . $config['region'];
$my_peers = curl_get_array($url, 20);
//if ($debug) {var_dump($my_peers); echo "\n";}

// Lookup peers we already know about in our local database
$sql = "SELECT screen_private_ip , screen_id , screen_region_name, screen_public_ip FROM my_peers;";
$known_peers = query_to_array($sql, &$mysqli);

//TODO: delete peers who have not check in, in over a week
$known_check = array();
foreach ($my_peers as $i=>$peer) {	
	// Is this peer "me"?  If so save config in settings
	if ($peer['screen_region_name'] == $config['region'] && $peer['screen_id'] == $config['screen_id']){
		if (isset($peer['screen_settings'])) {
			if (isset($peer['screen_settings']['change_speed'])) {
				save_setting('change_speed', $peer['screen_settings']['change_speed'], $mysqli);
			} else {
				save_setting('change_speed', 8, $mysqli);
			}
			if (isset($peer['screen_settings']['movie_override_speed'])) {
				save_setting('movie_override_speed', $peer['screen_settings']['movie_override_speed'], $mysqli);
			} else {
				save_setting('movie_override_speed', true, $mysqli);
			}
			if (isset($peer['screen_settings']['screen_group'])) {
				save_setting('screen_group', $peer['screen_settings']['screen_group'], $mysqli);
			} else {
				save_setting('screen_group', true, $mysqli);
			}
		}
	}
	
	// do we know about this peer (yea loop within a loop... not expecting more than 100 peers)
	$known_peer = false;
	foreach ($known_peers as $j=>$k_peer) {
		if ($peer['screen_region_name'] == $k_peer['screen_region_name'] && $peer['screen_id'] == $k_peer['screen_id']){
			$known_peer = true;
			
			// Did other info change, if so update in the local database
			if ($peer['screen_private_ip'] != $k_peer['screen_private_ip'] || $peer['screen_public_ip'] != $k_peer['screen_public_ip']) {
				$sql = "UPDATE my_peers SET screen_private_ip =" . sqlq($peer['screen_private_ip'], 0) 
				. ", screen_public_ip =" . sqlq($peer['screen_public_ip'], 0) . " WHERE screen_id = " 
				. sqlq($peer['screen_id'], 0) . " AND screen_region_name = " . sqlq($peer['screen_region_name'], 0);
				if ($debug) {echo "Running: $sql\n";}
				if (!$mysqli->query($sql)) {die("Update Failed: (" . $mysqli->errno . ") " . $mysqli->error);}
			}
			$known_check[$k_peer['screen_region_name'] . ':~:' . $k_peer['screen_id']] = true;
		}
		if (! isset($known_check[$k_peer['screen_region_name'] . ':~:' . $k_peer['screen_id']])) {
			$known_check[$k_peer['screen_region_name'] . ':~:' . $k_peer['screen_id']] = false;
		}
	}
	// If a new peer add them to the local db
	if (!$known_peer) {
		$sql = "INSERT INTO my_peers (screen_private_ip , screen_id , screen_region_name, screen_public_ip) VALUES (";
		$sql .= sqlq($peer['screen_private_ip'], 0) . ',';
		$sql .= sqlq($peer['screen_id'], 0) . ',';
		$sql .= sqlq($peer['screen_region_name'], 0) . ',';
		$sql .= sqlq($peer['screen_public_ip'], 0) . ')';
		if ($debug) {echo "Running: $sql\n";}
		if (!$mysqli->query($sql)) {die("Insert Failed: (" . $mysqli->errno . ") " . $mysqli->error);}
	}
}

//Delete any peers that have gone away
foreach ($known_check as $k=>$v) {
	if (! $v) {
		$name_id = explode(':~:', $k);
		$sql = "DELETE FROM my_peers WHERE screen_id = " . sqlq($name_id[1], 0) . " AND screen_region_name = " . sqlq($name_id[0], 0);
		if ($debug) {echo "Running: $sql\n";}
		if (!$mysqli->query($sql)) {die("Insert Failed: (" . $mysqli->errno . ") " . $mysqli->error);}		
	}
}


// Finally pull back peers in same region
$sql = "SELECT DISTINCT screen_private_ip FROM my_peers WHERE  screen_private_ip <> " . sqlq($my_ip,0) . " AND screen_region_name=" . sqlq($config['region'], 0);
$local_peers = query_to_array($sql, &$mysqli);
if ($debug) {echo var_dump($local_peers) . "\n";}
// End Self registration and awareness
/////////////////////////////////////////////////

/////////////////////////////////////////////////
// Now retreive what we're suppose to show next
// Build the my_media table schema on the fly
$sql = 'CREATE TABLE IF NOT EXISTS  my_media ('
	. ' media_id INT AUTO_INCREMENT PRIMARY KEY,'
	. ' media_path VARCHAR(767) NOT NULL,'
	. ' media_size int NOT NULL,'
	. ' media_type VARCHAR(32) NOT NULL,'
	. ' media_host VARCHAR(256) NULL,'
	. ' media_displayed DATETIME NULL,'
	. ' media_order INT);';
if ($debug) {echo "Running $sql\n";}
if (!$mysqli->query($sql)) {die("Table creation failed: (" . $mysqli->errno . ") " . $mysqli->error);}

// Do a little disk space management (if < 1000MB)
if ($disk_free < (1024 * 1024 * 1000)) {
	if ($debug) {echo "Low Disk Space ($disk_free) Doing Cleanup!\n";}	
	//remove most recently displayed files
	$sql = "SELECT media_order, media_path, media_size FROM my_media WHERE media_displayed is not null AND media_host = 'localhost' ORDER BY media_displayed desc limit 200";
	$remove_files = query_to_array($sql, &$mysqli);
	
	$tot_size = 0;
	foreach ($remove_files as $i=>$row) {
		if ($tot_size > 1024 * 1024 * 100) {break;} 
		if ($debug) {echo "Deleting:" . $config['media_folder'] . $row['media_path'] . "\n";}
		unlink($config['media_folder'] . $row['media_path']);
		// not going to do this for now, as it will be interesting to keep 
		// an ongoing log of what was shown when
		// $sql = "DELETE FROM my_media WHERE display_order=" . sqlq($row['display_order']), 1);
		// $dsql = query_to_array($sql, &$mysqli);
		$tot_size = $tot_size + $row['media_size'];
	}
  
}

// If we already have more than $file_batch_size in queue don't bother calling again 
// since we're calling faster than we're displaying
$sql = "SELECT media_id FROM my_media WHERE media_displayed is null LIMIT " . sqlq($file_batch_size, 1);
$in_queue = query_to_array($sql, &$mysqli);
if (count($in_queue) == $file_batch_size) { 
	if ($debug) {echo "Have more than $file_batch_size in queue wil not calling for more\n";}	
	exit;
}


// This returns the next 'X' files that this screen will display
$url = 'http://' . $config['master_server'] . '/photo-display/php/send_media_queue.php?'
  . '&screen_id=' . $config['screen_id'] 
  . '&length=' . $file_batch_size
  . '&region=' . $config['region'];

$confirm_reg = array();
$isql = array();
$my_media = curl_get_array($url, 20);
var_dump($my_media);
foreach ($my_media as $i=>$media) {
	// see if we have this locally
	$media_host = '';
	$filepath = $config['media_folder'] . $media['media_path'];
	$is_local = false;
	if (file_exists($filepath)) {
		if (filesize($filepath) > 4000) {
			if ($debug) {echo "$filepath found locally\n";}
			$confirm_reg[] = $media['media_path'];
			$media_host = 'localhost';
			$is_local = true; 
			$media_size = filesize($filepath);
		} else {
			if ($debug) {echo "$filepath exists but it too small getting again!\n";}
		}
	} 
	if (!$is_local) {
		//TODO: Dedup - if peers are off at times when other are on you can get reduntant data that could be cleaned up
		// see if a local peer has it
		foreach ($local_peers as $j => $peer) {
			$url = 'http://' . $peer['screen_private_ip'] . ':8080/find_media?media_path=' . urlencode($media['media_path']);
			$peer_media = curl_get_array($url, 1);
			if (isset($peer_media['found']) && $peer_media['found']) {
				$confirm_reg[] = $media['media_path'];
				$media_host = $peer['screen_private_ip'];
				$media_size = $peer_media['file_size'];
				break;
			}
		}
	}
	
	// Did we find locally or do we need to retreive it
	if ($media_host == '') {
		// media_size is returned from send_media_queue.php which can be used
		
		// Make sure the folder structure exists
		$dirpath = $config['media_folder'] . dirname($media['media_path']);
		if (! is_dir($dirpath)) {
			mkdir($dirpath, 0777, true);
			if ($debug) {echo "Made Directory $dirpath\n";}
		}
		
		$url = 'http://' . $config['master_server'] . '/photo-display/php/send_media.php?media_path=' . urlencode($media['media_path']);
		if ($debug) {echo "Calling: $url\n";}
		if (curl_write_file($url, $filepath)) {
			$confirm_reg[] = $media['media_path'];
			$media_host = 'localhost';
			$media_size = filesize($filepath);
			//TODO: Verify the checksum and store in my_media database
		}

	}
	

	if ($media_host != '') {
		$isql[] = "INSERT INTO my_media (media_path, media_type, media_size, media_host, media_order) VALUES ("
		. sqlq($media['media_path'], 0) . ','
		. sqlq($media['media_type'], 0) . ','
		. sqlq($media_size, 1) . ','
		. sqlq($media_host, 0) . ', (FLOOR( 1 + RAND( ) *6000000 ))); ';
	}
}

// Finally commit what we are going to show to the database and register it with the main host
$post_data = "medialist=" . urlencode(json_encode($confirm_reg));
$url = 'http://' . $config['master_server'] . '/photo-display/php/confirm_media_queue.php?'
  . '&screen_id=' . $config['screen_id'] 
  . '&region=' . $config['region'];
if ($debug) {echo "Calling: $url\n";}
if (curl_post_data($url, $post_data)) {
	foreach ($isql as $sql) {
		if ($debug) {echo "Running: $sql\n";}
		if (!$mysqli->query($sql)) {die("Insert Failed: (" . $mysqli->errno . ") " . $mysqli->error);}
	}
} else {
	if ($debug) {echo "Error will not update local database\n";}
}

// Close MySQL Connection
mysqli_close($mysqli);


function save_setting($name, $value, $mysqli) {
	global $debug;
	$sql = "INSERT INTO my_settings (setting_name, setting_value) VALUES (";
	$sql = $sql . sqlq($name,0) . ',' . sqlq($value,0) . ') on duplicate key update setting_value=' . sqlq($value,0);
	if ($debug) {echo "Running $sql\n";}
	if (!$mysqli->query($sql)) {die("Insert failed: (" . $mysqli->errno . ") " . $mysqli->error);}
}

?>
