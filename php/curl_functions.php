<?php

// CURL Functions
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
    echo 'error:' . curl_error($ch);
  } else {
    return json_decode($result, true);
  }
  return array();
}








?>
