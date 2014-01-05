<?php
// A php script that will talk to the main EC2 server, 
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


// Register yourself and learn about local peers
$url = 'http://MyEC2instance.com/find_peers.php?ip='
  . $_SERVER['SERVER_ADDR'] 
  . '&screen_id=' . $config['screen_id'] 
  . '&region=' . $config['region'];
  
$ch = curl_init();
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_URL,$url);
$result=curl_exec($ch);
if(curl_errno($c))
{
    echo 'error:' . curl_error($c);
} else {
  $my_peers = json_decode($result, true);
}





// Build the table schema on the fly
$sql = 'CREATE TABLE IF NOT EXISTS tblWords (Word varchar(32) NOT NULL, Shown bit NOT NULL, PositionNum INT, LastPosition INT);';
$result = mysql_query($sql, $link);
if (!$result) {die('Invalid query: ' . mysql_error() . "\n");}

$sql = "SELECT Word FROM tblWords;";
$result = mysql_query($sql, $link);
while ($row = mysql_fetch_assoc($result)) {
    $words[] = $row['Word'];
}
mysql_free_result($result);








$arr = array('cnt' => $_REQUEST['cnt'], 'duration' => $_REQUEST['duration']);

echo json_encode($arr);




// Close MySQL Connection
mysql_close($link);

?>
