<?php

// CURL Functions
// Call a URL that returns JSON and return the data as an array
function curl_get_array($url, $timeout) {
	global $debug;
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
	$to_ret = false;
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
	$to_ret = false;
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


?>
