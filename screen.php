<?php
// Script that browser that drives the "screens" initially loads
?>
<html>
<head><Title>Photo Display Screen</title>
<script src="//code.jquery.com/jquery-1.10.2.min.js"></script>
</head>
<body>
 
 <div id="my_media"></div>
 

 
<script>
$( "#my_media" ).text( 'this is a test' );
setTimeout(function(){$( "#my_media" ).text( 'this is a test2' );},3000);
setTimeout(function(){$( "#my_media" ).text( 'this is a test3' );},3000);

</script>
 
</body>
</html>
