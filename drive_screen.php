<?php
// This script is responsible for servicing the Ajax requests 
// the browsers running on the "screen" display devices make
//
// returns: JSON that details:
//    Next URL to show
//    New Media Type
//    Duration to Show Media
//    If a caption should be shown

$arr = array('a' => $_REQUEST['name'], 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5);

echo json_encode($arr);

?>
