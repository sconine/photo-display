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
// Build the my_peers table schema on the fly
$sql = 'CREATE TABLE IF NOT EXISTS my_peers (screen_region_name varchar(128) NOT NULL, screen_id varchar(128) NOT NULL, screen_private_ip varchar(32) NOT NULL, screen_public_ip varchar(32) NOT NULL, PRIMARY KEY (screen_region_name, screen_id));';
if (!$mysqli->query($sql)) {die("Table creation failed: (" . $mysqli->errno . ") " . $mysqli->error);}
if ($debug) {echo 'my_peers table Exists'. "\n";}


// Call the central public registration server
$url = 'http://' . $config['master_server'] . '/photo-display/php/find_peers.php?screen_private_ip=' . $my_ip
  . '&screen_id=' . $config['screen_id'] 
  . '&screen_public_ip=' . $config['public_ip'] 
  . '&screen_region_name=' . $config['region'];
$my_peers = curl_get_array($url, 20);
//if ($debug) {var_dump($my_peers); echo "\n";}

// Lookup peers we already know about in our local database
$sql = "SELECT screen_private_ip , screen_id , screen_region_name, screen_public_ip FROM my_peers;";
$known_peers = query_to_array($sql, &$mysqli);

foreach ($my_peers as $i=>$peer) {
	//if ($debug) {echo "Checking: " . $peer['screen_id'] . " " . $peer['screen_region_name'] . "\n";}
	
	// do we know about this peer (yea loop within a loop... not expecting more than 100 peers)
	$known_peer = false;
	foreach ($known_peers as $j=>$k_peer) {
		//if ($debug) {echo "Found: " . $k_peer['screen_id'] . " " . $k_peer['screen_region_name'] . "\n";}
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

// Finally pull back peers in same region
$sql = "SELECT DISTINCT screen_private_ip FROM my_peers WHERE screen_region_name=" . sqlq($config['region'], 0);
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

// Do a little disk space management (if < 100MB)
if (disk_free_space($config['media_folder']) < (1024 * 1024 * 100)) {
	//remove most recently displayed files
	$sql = "SELECT display_order, media_path, media_size FROM my_media WHERE displayed is not null AND media_host = 'localhost' ORDER BY displayed desc limit 200";
	$remove_files = query_to_array($sql, &$mysqli);
	
	$tot_size = 0;
	foreach ($remove_files as $i=>$row) {
		if ($tot_size > 1024 * 1024 * 100) {break;} 
		unlink($config['media_folder'] . $row['media_path']);
		// not going to do this for now, as it will be interesting to keep 
		// an ongoing log of what was shown when
		// $sql = "DELETE FROM my_media WHERE display_order=" . sqlq($row['display_order']), 1);
		// $dsql = query_to_array($sql, &$mysqli);
		$tot_size = $tot_size + $row['media_size'];
	}
  
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
			$confirm_reg[] = $media['media_path'];
			$media_host = 'localhost';
			$is_local = true; 
		} else {
			if ($debug) {echo "$filepath exists but it too small getting again!\n";}
		}
	} 
	if (!$is_local) {
		// see if a local peer has it
		foreach ($local_peers as $j => $peer) {
			$url = 'http://' . $peer['screen_private_ip'] . ':8080/find_media?media_path=' . urlencode($media['media_path']);
			$peer_media = curl_get_array($url, 1);
			if (isset($peer_media['found']) && $peer_media['found']) {
				$confirm_reg[] = $media['media_path'];
				$media_host = $peer['screen_private_ip'];
				break;
			}
		}
	}
	
	// Did we find locally or do we need to retreive it
	if ($media_host == '') {
		//TODO: add disk space checks/cleanup and check file size prior to downloading
		//TODO: Make sure we didn't just get junk data from an nginx error
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
		}

	}
	
	if ($media_host != '') {
		$isql[] = "INSERT INTO my_media (media_path, media_type, media_size, media_host, media_order) VALUES ("
		. sqlq($media['media_path'], 0) . ','
		. sqlq($media['media_type'], 0) . ','
		. sqlq(filesize($filepath), 1) . ','
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




?>
