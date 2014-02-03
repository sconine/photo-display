$post_data = "medialist=" & urlencode(json_encode($confirm_reg));
$url = 'http://' . $config['master_server'] . '/confirm_media_queue.php?'
  . '&screen_id=' . $config['screen_id'] 
  . '&region=' . $config['region'];
