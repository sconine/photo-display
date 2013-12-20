<?php
// This script is responsible for servicing the Ajax requests 
// the browsers running on the "screen" display devices make
//
// returns: JSON that details:
//    Next URL to show
//    New Media Type
//    Duration to Show Media
//    If a caption should be shown

$arr = array('cnt' => $_REQUEST['cnt'], 'duration' => $_REQUEST['duration']);

echo json_encode($arr);

?>
