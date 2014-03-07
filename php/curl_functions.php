<?php

// CURL Functions
// Call a URL that returns JSON and return the data as an array
function curl_get_array($url, $timeout) {
	global $debug;
	$url = add_token($url);
	if ($debug) {echo "Calling: $url \n";}
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,$timeout); 
	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout); //timeout in seconds
	$result=curl_exec($ch);
	if(curl_errno($ch)) {
		if ($debug) {echo 'error:' . curl_error($ch) . "\n";}
	} else {
		if (curl_status_200(curl_getinfo($ch))) {
			return json_decode($result, true);
		} 		
	}
	return array();
}

// Call a URL that returns data and write whatever is returned to a file
function curl_write_file($url, $filepath) {
	global $debug;
	$url = add_token($url);
	$to_ret = false;
	if ($debug) {echo "Calling: $url\n";}
	// Since downloads can take a while set the timeout to long 
	set_time_limit(0);
	$fp = fopen ($filepath, 'w+');
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_TIMEOUT, 600);
	curl_setopt($ch, CURLOPT_FILE, $fp); 
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_exec($ch); 
	fclose($fp);
	if(curl_errno($ch)) {
		if ($debug) {echo 'Curl error:' . curl_error($ch) . "\n";}
		unlink($filepath);
	} else {
		if (curl_status_200(curl_getinfo($ch))) {
			// Make sure the file has some size to it
			if (filesize($filepath) > 2000) {
				$to_ret = true;
			} else {
				unlink($filepath);
			}
		} else {
			unlink($filepath);
		}
	}
	curl_close($ch);
	return $to_ret;
}

// Call a URL and pose data, return true or false for success or failure
function curl_post_data($url, $post_data) {
	global $debug;
	$url = add_token($url);
	$to_ret = false;
	if ($debug) {echo "Calling: $url\n";}
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_POSTFIELDS,  $post_data);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,4); 
	curl_setopt($ch, CURLOPT_TIMEOUT, 5); //timeout in seconds
	$result=curl_exec($ch);
	if(curl_errno($ch)) {
		if ($debug) {echo 'Curl error: ' . curl_error($ch);}
	} else {
		if (curl_status_200(curl_getinfo($ch))) {
			$to_ret = true;
		}  
	}
	return $to_ret;
}

// Check for 200 status return code
function curl_status_200($info) {
	global $debug;
	if (empty($info['http_code'])) {
		if ($debug) {echo "No HTTP Status Code Returned\n";}
		return false;
	} else if ($info['http_code'] == '200') {
		return true;
	} else {
		if ($debug) {echo "Non 200 Status Code of " . $info['http_code'] . " Returned\n";}
		return false;
	}  
}

// add a token for verification check
function add_token($url) {
	global $debug;
	global $config;

	// Only works if $config is loaded
	if  (isset($config['my_key'])) {
		$m_time = microtime();
		$s_time = time();
		$enc = md5($s_time . $m_time . $config['screen_id'] . ':' . $config['my_key']);
		if (!strpos($url, '?')) {
			$url = $url . '?';
		} else {
			$url = $url . '&';
		}
		$url = $url . 'enc=' . urlencode($s_time . ":" . $m_time . ":" . $config['screen_id'] . ":" . $enc);
		
	}
	return $url;
}


// Validate a token that was sent
function check_token($token, $mysqli) {
	global $debug;
	global $config;
$debug = true;
	$parts = explode(':', $token);
	if (count($parts) != 4) {
		echo 'Mal-formed token';
		return false;
	}

	// Only works if $config is loaded
	if  (! isset($config['my_key'])) {
		echo 'my_key not configured';
		return false;
	}
	
	$cur_time = time();
	$four_hr = 60*60*4;
	$clean_time = $cur_time - (60*60*24);
	$s_time = $parts[0];
	$m_time = $parts[1];
	$screen_id = $parts[2];
	$compare = $parts[3];
	$compare_with = md5($s_time . $m_time . $screen_id . ':' . $config['my_key']);
	
	// First make sure the hash matches
	if  ($compare != $compare_with) {
		echo 'Hash does not match';
		return false;
	}	
	
	// First make sure this time is within 4 hours of now (so we can safetly clean out old ones)
	if ($s_time < ($cur_time - $four_hr) || $s_time > ($cur_time + $four_hr)) {
		echo 'Time Stamp Off';
		return false;
	}
	
	// See if we've already used this timestamp for this screen
	// Build the  table schema on the fly
	$sql = 'CREATE TABLE IF NOT EXISTS my_tokens (s_time int NOT NULL, m_time varchar(64) NOT NULL, screen_id varchar(128) NOT NULL, PRIMARY KEY (s_time, m_time, screen_id));';
	if (!$mysqli->query($sql)) {die("Table creation failed: (" . $mysqli->errno . ") " . $mysqli->error);}

	$sql = "SELECT s_time FROM my_tokens WHERE s_time = " . sqlq($s_time, 0) . " AND m_time = " . sqlq($m_time, 0) . " AND screen_id=" . sqlq($screen_id, 0) . ";";
	$tokens = query_to_array($sql, $mysqli);
	if (count($tokens) > 0) {
		echo 'Token already used';
		return false;
	}
	
	// otherwise insert this token and cleanup old ones
	$sql = "INSERT INTO my_tokens (s_time , m_time , screen_id) VALUES (";
	$sql .= sqlq($s_time, 0) . ',';
	$sql .= sqlq($m_time, 0) . ',';
	$sql .= sqlq($screen_id, 0) . ')';
	if ($debug) {echo "Running: $sql\n";}
//	if (!$mysqli->query($sql)) {die("Insert Failed: (" . $mysqli->errno . ") " . $mysqli->error);}

	$sql = "DELETE FROM my_tokens WHERE s_time < " .  sqlq($clean_time, 1);
	if ($debug) {echo "Running: $sql\n";}
	if (!$mysqli->query($sql)) {die("Delete Failed: (" . $mysqli->errno . ") " . $mysqli->error);}
	
	return true;
}

?>
